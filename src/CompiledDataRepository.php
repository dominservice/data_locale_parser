<?php

declare(strict_types=1);

namespace Dominservice\DataLocaleParser;

use RuntimeException;

final class CompiledDataRepository
{
    /** @var array<string, array<mixed>> */
    private static array $manifestCache = [];

    /** @var array<string, array<mixed>> */
    private array $payloadCache = [];

    /** @var array<mixed> */
    private array $manifest;

    public function __construct(private string $dataDirectory)
    {
        $manifestPath = $this->dataDirectory.'/manifest.php';

        if (!isset(self::$manifestCache[$manifestPath])) {
            if (!is_file($manifestPath)) {
                throw new RuntimeException(sprintf(
                    'Unable to locate the compiled data manifest at "%s"',
                    $manifestPath
                ));
            }

            $manifest = require $manifestPath;

            if (!is_array($manifest) || ($manifest['schema'] ?? null) !== 2) {
                throw new RuntimeException(sprintf(
                    'Compiled data manifest "%s" has an unsupported schema.',
                    $manifestPath
                ));
            }

            self::$manifestCache[$manifestPath] = $manifest;
        }

        $this->manifest = self::$manifestCache[$manifestPath];
    }

    /**
     * @return array<string, string>
     */
    public function load(string $type, string $locale): array
    {
        $definition = $this->manifest['types'][$type] ?? null;

        if (!is_array($definition)) {
            throw new RuntimeException(sprintf('Unknown compiled data type "%s".', $type));
        }

        $runtimeDirectory = (string) ($definition['directory'] ?? '');
        $canonicalLocale = $definition['locales'][$locale] ?? null;

        if (!is_string($canonicalLocale)) {
            $singular = $this->singularType($type);
            $legacyPath = sprintf(
                '%s/%s/%s/%s.php',
                $this->dataDirectory,
                $runtimeDirectory,
                $locale,
                $singular
            );

            throw new RuntimeException(sprintf(
                'Unable to load the country data file "%s"',
                $legacyPath
            ));
        }

        $cacheKey = $runtimeDirectory.'/'.$canonicalLocale;

        if (!isset($this->payloadCache[$cacheKey])) {
            $path = sprintf(
                '%s/%s/%s.php',
                $this->dataDirectory,
                $runtimeDirectory,
                $canonicalLocale
            );

            if (!is_file($path)) {
                throw new RuntimeException(sprintf(
                    'Compiled data payload "%s" referenced by %s/%s does not exist.',
                    $path,
                    $type,
                    $locale
                ));
            }

            $payload = require $path;

            if (!is_array($payload)) {
                throw new RuntimeException(sprintf(
                    'Compiled data payload "%s" must return an array.',
                    $path
                ));
            }

            $this->payloadCache[$cacheKey] = $payload;
        }

        return $this->payloadCache[$cacheKey];
    }

    /**
     * @return list<string>
     */
    public function locales(string $type): array
    {
        $locales = array_keys($this->manifest['types'][$type]['locales'] ?? []);
        sort($locales, SORT_STRING);

        return $locales;
    }

    private function singularType(string $type): string
    {
        return match ($type) {
            'countries' => 'country',
            'currencies' => 'currency',
            'languages' => 'language',
            default => '__',
        };
    }
}
