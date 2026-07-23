<?php

declare(strict_types=1);

namespace Dominservice\DataLocaleParser\Build;

use RuntimeException;

final class Validator
{
    /** @var array<mixed> */
    private array $contract;

    public function __construct(string $contractPath)
    {
        $this->contract = Json::read($contractPath);
    }

    /**
     * @return array<mixed>
     */
    public function validate(string $dataDirectory): array
    {
        $manifestPath = $dataDirectory.'/manifest.php';

        if (!is_file($manifestPath)) {
            throw new RuntimeException(sprintf('Missing compiled manifest "%s".', $manifestPath));
        }

        $manifest = require $manifestPath;

        if (!is_array($manifest) || ($manifest['schema'] ?? null) !== 2
            || ($manifest['layout'] ?? null) !== 'canonical-locale-files') {
            throw new RuntimeException(sprintf('Invalid compiled manifest "%s".', $manifestPath));
        }

        $result = [
            'schema' => 1,
            'source' => $manifest['source'] ?? null,
            'types' => [],
        ];

        foreach ($this->contract['types'] ?? [] as $type => $expected) {
            if (!is_string($type) || !is_array($expected)) {
                throw new RuntimeException('The runtime contract contains an invalid type.');
            }

            $actual = $manifest['types'][$type] ?? null;

            if (!is_array($actual)) {
                throw new RuntimeException(sprintf('Manifest type "%s" is missing.', $type));
            }

            $expectedLocales = $expected['locales'] ?? [];
            $keySets = $expected['key_sets'] ?? [];
            $localeKeySets = $expected['locale_key_sets'] ?? [];
            $actualLocales = array_keys($actual['locales'] ?? []);

            if ($actualLocales !== $expectedLocales) {
                throw new RuntimeException(sprintf(
                    'Manifest locales for "%s" do not match the public runtime contract.',
                    $type
                ));
            }

            $directory = (string) ($actual['directory'] ?? '');
            $referencedFiles = [];
            $payloadCache = [];
            $contentReferences = [];
            $keyCounts = [];

            foreach ($actual['locales'] as $locale => $canonicalLocale) {
                if (!is_string($locale) || !is_string($canonicalLocale)
                    || preg_match('/^[A-Za-z0-9_]+$/', $canonicalLocale) !== 1) {
                    throw new RuntimeException(sprintf(
                        'Manifest reference for "%s/%s" is invalid.',
                        $type,
                        (string) $locale
                    ));
                }

                if (!isset($actual['locales'][$canonicalLocale])
                    || $actual['locales'][$canonicalLocale] !== $canonicalLocale) {
                    throw new RuntimeException(sprintf(
                        'Canonical reference "%s" for "%s/%s" does not map to itself.',
                        $canonicalLocale,
                        $type,
                        $locale
                    ));
                }

                $payloadPath = $dataDirectory.'/'.$directory.'/'.$canonicalLocale.'.php';

                if (!is_file($payloadPath)) {
                    throw new RuntimeException(sprintf(
                        'Payload for "%s/%s" is missing: "%s".',
                        $type,
                        $locale,
                        $payloadPath
                    ));
                }

                $payloadCache[$canonicalLocale] ??= require $payloadPath;
                $payload = $payloadCache[$canonicalLocale];
                $keySetId = $localeKeySets[$locale] ?? null;
                $expectedKeys = is_string($keySetId) ? ($keySets[$keySetId] ?? null) : null;

                if (!is_array($payload) || !is_array($expectedKeys)
                    || array_keys($payload) !== $expectedKeys) {
                    throw new RuntimeException(sprintf(
                        'Payload keys for "%s/%s" do not match the public runtime contract.',
                        $type,
                        $locale
                    ));
                }
                $keyCounts[] = count($expectedKeys);

                foreach ($payload as $key => $value) {
                    if (!is_string($key) || !is_string($value)) {
                        throw new RuntimeException(sprintf(
                            'Payload "%s/%s" contains a non-string key or value.',
                            $type,
                            $locale
                        ));
                    }
                }

                $actualHash = hash('sha256', serialize($payload));

                if (isset($contentReferences[$actualHash])
                    && $contentReferences[$actualHash] !== $canonicalLocale) {
                    throw new RuntimeException(sprintf(
                        'Payloads "%s/%s.php" and "%s/%s.php" are duplicates.',
                        $type,
                        $contentReferences[$actualHash],
                        $type,
                        $canonicalLocale
                    ));
                }

                $contentReferences[$actualHash] = $canonicalLocale;
                $referencedFiles[realpath($payloadPath)] = true;
            }

            $storedFiles = glob($dataDirectory.'/'.$directory.'/*.php') ?: [];

            foreach ($storedFiles as $storedFile) {
                if (!isset($referencedFiles[realpath($storedFile)])) {
                    throw new RuntimeException(sprintf(
                        'Unreferenced compiled payload "%s".',
                        $storedFile
                    ));
                }
            }

            if (count($storedFiles) !== count($referencedFiles)) {
                throw new RuntimeException(sprintf(
                    'Compiled payload count for "%s" is inconsistent.',
                    $type
                ));
            }

            $result['types'][$type] = [
                'locale_count' => count($actualLocales),
                'key_count_min' => min($keyCounts),
                'key_count_max' => max($keyCounts),
                'key_set_count' => count($keySets),
                'payload_count' => count($storedFiles),
                'deduplicated_locale_count' => count($actualLocales) - count($storedFiles),
            ];
        }

        return $result;
    }
}
