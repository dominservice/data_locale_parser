<?php

declare(strict_types=1);

namespace Dominservice\DataLocaleParser\Build;

use RuntimeException;

final class Compiler
{
    /** @var array<mixed> */
    private array $contract;

    /** @var array<mixed> */
    private array $compatibility;

    /** @var array<string, array<mixed>> */
    private array $languageAliases;

    /** @var array<string, string> */
    private array $likelySubtags;

    /** @var array<string, string> */
    private array $parentLocales;

    /** @var list<string> */
    private array $defaultContent;

    /**
     * @param array<string, string> $sources
     */
    public function __construct(
        string $contractPath,
        string $compatibilityPath,
        private array $sources,
        private string $cldrVersion
    ) {
        $this->contract = Json::read($contractPath);
        $this->compatibility = Json::read($compatibilityPath);
        $core = $sources['cldr-core'] ?? null;

        if ($core === null) {
            throw new RuntimeException('The cldr-core source is required.');
        }

        $aliases = Json::read($core.'/supplemental/aliases.json');
        $likely = Json::read($core.'/supplemental/likelySubtags.json');
        $parents = Json::read($core.'/supplemental/parentLocales.json');
        $defaultContent = Json::read($core.'/defaultContent.json');

        $this->languageAliases =
            $aliases['supplemental']['metadata']['alias']['languageAlias'] ?? [];
        $this->likelySubtags = $likely['supplemental']['likelySubtags'] ?? [];
        $this->parentLocales =
            $parents['supplemental']['parentLocales']['parentLocale'] ?? [];
        $this->defaultContent = $defaultContent['defaultContent'] ?? [];
    }

    /**
     * @return array<mixed>
     */
    public function compile(string $outputDirectory, ?string $legacyDataDirectory = null): array
    {
        $this->assertEmptyOutput($outputDirectory);
        $manifest = [
            'schema' => 2,
            'layout' => 'canonical-locale-files',
            'source' => [
                'name' => 'Unicode CLDR',
                'version' => $this->cldrVersion,
            ],
            'types' => [],
        ];
        $report = [
            'schema' => 1,
            'source' => $manifest['source'],
            'types' => [],
            'compatibility_overlay' => [
                'value_count' => 0,
                'by_type' => [],
            ],
        ];

        foreach ($this->contract['types'] ?? [] as $type => $definition) {
            if (!is_string($type) || !is_array($definition)) {
                throw new RuntimeException('The runtime contract contains an invalid type.');
            }

            [$typeManifest, $typeReport] = $this->compileType(
                $type,
                $definition,
                $outputDirectory,
                $legacyDataDirectory
            );
            $manifest['types'][$type] = $typeManifest;
            $report['types'][$type] = $typeReport;
            $overlayCount = (int) ($typeReport['compatibility_overlay_values'] ?? 0);
            $report['compatibility_overlay']['by_type'][$type] = $overlayCount;
            $report['compatibility_overlay']['value_count'] += $overlayCount;
        }

        $this->writeManifestPhp($outputDirectory.'/manifest.php', $manifest);
        $report['compiled'] = $this->directoryMetrics($outputDirectory);

        return $report;
    }

    /**
     * @param array<mixed> $definition
     * @return array{0: array<mixed>, 1: array<mixed>}
     */
    private function compileType(
        string $type,
        array $definition,
        string $outputDirectory,
        ?string $legacyDataDirectory
    ): array {
        $runtimeDirectory = (string) ($definition['directory'] ?? '');
        $package = (string) ($definition['package'] ?? '');
        $file = (string) ($definition['file'] ?? '');
        $path = $definition['path'] ?? [];
        $locales = $definition['locales'] ?? [];
        $keySets = $definition['key_sets'] ?? [];
        $localeKeySets = $definition['locale_key_sets'] ?? [];
        $sourceRoot = $this->sources[$package] ?? null;

        if ($runtimeDirectory === '' || $sourceRoot === null || !is_array($path)
            || !is_array($locales) || !is_array($keySets) || !is_array($localeKeySets)) {
            throw new RuntimeException(sprintf('Invalid runtime contract for type "%s".', $type));
        }

        $available = $this->availableLocales($sourceRoot, $file);
        $englishValues = $this->readSourceValues(
            $sourceRoot.'/main/en/'.$file,
            'en',
            $path
        );
        $resolver = new LocaleResolver(
            $available,
            $this->languageAliases,
            $this->likelySubtags,
            $this->parentLocales,
            $this->defaultContent
        );
        $payloadFiles = [];
        $payloadLocales = [];
        $resolutions = [];
        $fallbacks = [];
        $changes = [];
        $changesByLocale = [];
        $legacyValueCount = 0;
        $changedValueCount = 0;
        $unchangedValueCount = 0;
        $keyCounts = [];

        foreach ($locales as $locale) {
            if (!is_string($locale)) {
                throw new RuntimeException(sprintf('Invalid locale in type "%s".', $type));
            }

            $resolved = $resolver->resolve($locale);
            $sourceValues = $this->readSourceValues(
                $sourceRoot.'/main/'.$resolved['locale'].'/'.$file,
                $resolved['locale'],
                $path
            );
            $payload = [];
            $keySetId = $localeKeySets[$locale] ?? null;
            $keys = is_string($keySetId) ? ($keySets[$keySetId] ?? null) : null;

            if (!is_array($keys)) {
                throw new RuntimeException(sprintf(
                    'Missing key contract for %s/%s.',
                    $type,
                    $locale
                ));
            }

            $keyCounts[] = count($keys);

            foreach ($keys as $key) {
                if (!is_string($key)) {
                    throw new RuntimeException(sprintf('Invalid key in type "%s".', $type));
                }

                [$value, $fallback] = $this->valueFor(
                    $type,
                    $locale,
                    $key,
                    $sourceValues,
                    $englishValues
                );
                $payload[$key] = $value;

                if ($fallback !== null) {
                    $fallbacks[$locale][$key] = $fallback;
                }
            }

            $hash = hash('sha256', serialize($payload));

            if (isset($payloadFiles[$hash]) && $payloadFiles[$hash] !== $payload) {
                throw new RuntimeException(sprintf(
                    'SHA-256 collision detected while compiling %s/%s.',
                    $type,
                    $locale
                ));
            }

            $payloadFiles[$hash] ??= $payload;
            $payloadLocales[$hash][] = $locale;
            $resolutions[$locale] = $resolved;

            if ($legacyDataDirectory !== null) {
                $legacyFile = $legacyDataDirectory.'/'.$runtimeDirectory.'/'.$locale.'/'.$runtimeDirectory.'.php';

                if (is_file($legacyFile)) {
                    $legacy = require $legacyFile;

                    if (!is_array($legacy)) {
                        throw new RuntimeException(sprintf('Legacy data file "%s" must return an array.', $legacyFile));
                    }

                    $localeChanges = 0;

                    foreach ($payload as $key => $value) {
                        $legacyValueCount++;

                        if (($legacy[$key] ?? null) === $value) {
                            $unchangedValueCount++;
                        } else {
                            $changedValueCount++;
                            $localeChanges++;

                            if (count($changes) < 100) {
                                $changes[] = [
                                    'locale' => $locale,
                                    'code' => $key,
                                    'before' => $legacy[$key] ?? null,
                                    'after' => $value,
                                ];
                            }
                        }
                    }

                    if ($localeChanges > 0) {
                        $changesByLocale[$locale] = $localeChanges;
                    }
                }
            }
        }

        $referencesByLocale = [];

        foreach ($payloadFiles as $hash => $payload) {
            $canonicalLocale = $this->canonicalLocale($payloadLocales[$hash]);
            $this->writePayloadPhp(
                $outputDirectory.'/'.$runtimeDirectory.'/'.$canonicalLocale.'.php',
                $payload
            );

            foreach ($payloadLocales[$hash] as $locale) {
                $referencesByLocale[$locale] = $canonicalLocale;
            }
        }

        $localeMap = [];

        foreach ($locales as $locale) {
            $localeMap[$locale] = $referencesByLocale[$locale];
        }

        ksort($changesByLocale);
        $legacyFallbackCount = 0;
        $cldrFallbackCount = 0;
        $fallbackCountsBySource = [];
        $fallbackCountsByLocale = [];
        $fallbackExamples = [];

        foreach ($fallbacks as $fallbackLocale => $localeFallbacks) {
            foreach ($localeFallbacks as $fallbackKey => $fallback) {
                $valueSource = (string) ($fallback['value_source'] ?? 'unknown');
                $fallbackCountsBySource[$valueSource] =
                    ($fallbackCountsBySource[$valueSource] ?? 0) + 1;
                $fallbackCountsByLocale[$fallbackLocale][$valueSource] =
                    ($fallbackCountsByLocale[$fallbackLocale][$valueSource] ?? 0) + 1;

                if (count($fallbackExamples) < 100) {
                    $fallbackExamples[] = [
                        'locale' => $fallbackLocale,
                        'code' => $fallbackKey,
                    ] + $fallback;
                }

                if ($valueSource === 'legacy-compatibility-overlay') {
                    $legacyFallbackCount++;
                } elseif ($valueSource === 'cldr-en-fallback') {
                    $cldrFallbackCount++;
                }
            }
        }

        $typeManifest = [
            'directory' => $runtimeDirectory,
            'key_count_min' => min($keyCounts),
            'key_count_max' => max($keyCounts),
            'locales' => $localeMap,
        ];
        $typeReport = [
            'locale_count' => count($locales),
            'key_count_min' => min($keyCounts),
            'key_count_max' => max($keyCounts),
            'key_set_count' => count($keySets),
            'unique_payload_count' => count($payloadFiles),
            'deduplicated_locale_count' => count($locales) - count($payloadFiles),
            'compatibility_overlay_values' => $legacyFallbackCount,
            'cldr_english_fallback_values' => $cldrFallbackCount,
            'cldr_identifier_alias_values' =>
                $fallbackCountsBySource['cldr-identifier-alias'] ?? 0,
            'source_locale_resolutions' => $resolutions,
            'fallbacks' => [
                'counts_by_source' => $fallbackCountsBySource,
                'counts_by_locale' => $fallbackCountsByLocale,
                'first_100' => $fallbackExamples,
            ],
            'legacy_comparison' => [
                'value_count' => $legacyValueCount,
                'unchanged_value_count' => $unchangedValueCount,
                'changed_value_count' => $changedValueCount,
                'changed_locale_count' => count($changesByLocale),
                'changed_values_by_locale' => $changesByLocale,
                'first_100_changes' => $changes,
            ],
        ];

        return [$typeManifest, $typeReport];
    }

    /**
     * @param array<mixed> $sourceValues
     * @param array<mixed> $englishValues
     * @return array{0: string, 1: array<string, string>|null}
     */
    private function valueFor(
        string $type,
        string $locale,
        string $key,
        array $sourceValues,
        array $englishValues
    ): array {
        $sourceKey = str_replace('_', '-', $key);
        $sourceKeys = [$sourceKey];

        if ($type === 'languages') {
            $alias = $this->languageAliases[$sourceKey] ?? null;

            if (is_array($alias) && isset($alias['_replacement'])) {
                $sourceKeys[] = (string) $alias['_replacement'];
            }
        }

        foreach ($sourceKeys as $candidateKey) {
            $source = $this->extractValue($type, $sourceValues[$candidateKey] ?? null);

            if (is_string($source)) {
                return [
                    $source,
                    $candidateKey === $sourceKey ? null : [
                        'reason' => 'identifier-replaced-by-cldr-alias',
                        'value_source' => 'cldr-identifier-alias',
                        'source_key' => $candidateKey,
                    ],
                ];
            }
        }

        foreach ($sourceKeys as $candidateKey) {
            $english = $this->extractValue($type, $englishValues[$candidateKey] ?? null);

            if (is_string($english)) {
                return [
                    $english,
                    [
                        'reason' => 'value-not-present-in-resolved-cldr-locale',
                        'value_source' => 'cldr-en-fallback',
                        'source_key' => $candidateKey,
                    ],
                ];
            }
        }

        $compatibility = $this->compatibility['values'][$type][$locale][$key] ?? null;

        if (is_string($compatibility)) {
            return [
                $compatibility,
                [
                    'reason' => 'identifier-not-present-in-cldr',
                    'value_source' => 'legacy-compatibility-overlay',
                ],
            ];
        }

        throw new RuntimeException(sprintf(
            'Missing CLDR value for %s/%s/%s (source key "%s").',
            $type,
            $locale,
            $key,
            implode('", "', $sourceKeys)
        ));
    }

    /**
     * @param mixed $value
     */
    private function extractValue(string $type, $value): ?string
    {
        if ($type === 'currencies' && is_array($value)) {
            $value = $value['displayName'] ?? null;
        }

        return is_string($value) ? $value : null;
    }

    /**
     * @param list<string> $path
     * @return array<mixed>
     */
    private function readSourceValues(
        string $sourceFile,
        string $resolvedLocale,
        array $path
    ): array {
        $json = Json::read($sourceFile);
        $value = $json['main'][$resolvedLocale] ?? null;

        foreach ($path as $segment) {
            if (!is_string($segment) || !is_array($value) || !array_key_exists($segment, $value)) {
                throw new RuntimeException(sprintf(
                    'Unable to read path "%s" from "%s".',
                    implode('.', $path),
                    $sourceFile
                ));
            }

            $value = $value[$segment];
        }

        if (!is_array($value)) {
            throw new RuntimeException(sprintf('Source payload "%s" must be an object.', $sourceFile));
        }

        return $value;
    }

    /**
     * @return list<string>
     */
    private function availableLocales(string $sourceRoot, string $file): array
    {
        $files = glob($sourceRoot.'/main/*/'.$file) ?: [];
        $locales = array_map(static fn (string $path): string => basename(dirname($path)), $files);
        sort($locales, SORT_STRING);

        return $locales;
    }

    /**
     * @param array<mixed> $value
     */
    private function writePayloadPhp(string $path, array $value): void
    {
        $lines = ["<?php", 'return ['];

        foreach ($value as $key => $item) {
            $lines[] = var_export($key, true).'=>'.var_export($item, true).',';
        }

        $lines[] = '];';
        $lines[] = '';
        $this->writeFile($path, implode("\n", $lines));
    }

    /**
     * @param array<mixed> $value
     */
    private function writeManifestPhp(string $path, array $value): void
    {
        $this->writeFile(
            $path,
            "<?php\n\ndeclare(strict_types=1);\n\nreturn ".var_export($value, true).";\n"
        );
    }

    private function writeFile(string $path, string $contents): void
    {
        $directory = dirname($path);

        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new RuntimeException(sprintf('Unable to create directory "%s".', $directory));
        }

        if (file_put_contents($path, $contents) === false) {
            throw new RuntimeException(sprintf('Unable to write PHP data file "%s".', $path));
        }
    }

    /**
     * @param list<string> $locales
     */
    private function canonicalLocale(array $locales): string
    {
        usort($locales, static function (string $left, string $right): int {
            return substr_count($left, '_') <=> substr_count($right, '_')
                ?: strlen($left) <=> strlen($right)
                ?: strcmp($left, $right);
        });

        $canonical = $locales[0] ?? '';

        if ($canonical === '' || preg_match('/^[A-Za-z0-9_]+$/', $canonical) !== 1) {
            throw new RuntimeException(sprintf(
                'Unable to choose a safe canonical locale from: %s.',
                implode(', ', $locales)
            ));
        }

        return $canonical;
    }

    private function assertEmptyOutput(string $outputDirectory): void
    {
        if (!is_dir($outputDirectory)) {
            if (!mkdir($outputDirectory, 0777, true) && !is_dir($outputDirectory)) {
                throw new RuntimeException(sprintf('Unable to create output directory "%s".', $outputDirectory));
            }

            return;
        }

        $entries = array_values(array_diff(scandir($outputDirectory) ?: [], ['.', '..']));

        if ($entries !== []) {
            throw new RuntimeException(sprintf(
                'Output directory "%s" must be empty to prevent accidental data loss.',
                $outputDirectory
            ));
        }
    }

    /**
     * @return array{file_count: int, logical_bytes: int, allocated_bytes: int}
     */
    private function directoryMetrics(string $directory): array
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)
        );
        $fileCount = 0;
        $logicalBytes = 0;
        $allocatedBytes = 0;

        foreach ($files as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $fileCount++;
            $size = $file->getSize();
            $logicalBytes += $size;
            $allocatedBytes += (int) (ceil($size / 4096) * 4096);
        }

        return [
            'file_count' => $fileCount,
            'logical_bytes' => $logicalBytes,
            'allocated_bytes' => $allocatedBytes,
        ];
    }
}
