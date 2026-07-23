<?php

declare(strict_types=1);

namespace Dominservice\DataLocaleParser\Tests\Unit;

use Dominservice\DataLocaleParser\CompiledDataRepository;
use Dominservice\DataLocaleParser\Tests\TestCase;

final class CompiledDataRepositoryTest extends TestCase
{
    public function test_manifest_and_payload_store_are_complete_readable_and_deduplicated(): void
    {
        $root = dirname(__DIR__, 2);
        $manifest = require $root.'/data/manifest.php';
        $contract = json_decode(
            (string) file_get_contents($root.'/tools/data-builder/config/runtime-contract.json'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        self::assertSame(2, $manifest['schema']);
        self::assertSame('canonical-locale-files', $manifest['layout']);
        self::assertSame('Unicode CLDR', $manifest['source']['name']);
        self::assertSame('48.2.0', $manifest['source']['version']);

        foreach ([
            'countries' => ['directory' => 'country', 'locales' => 628, 'min' => 249, 'max' => 255],
            'currencies' => ['directory' => 'currency', 'locales' => 566, 'min' => 285, 'max' => 285],
            'languages' => ['directory' => 'language', 'locales' => 564, 'min' => 609, 'max' => 609],
        ] as $type => $expected) {
            $definition = $manifest['types'][$type];
            self::assertSame($expected['directory'], $definition['directory']);
            self::assertSame($expected['min'], $definition['key_count_min']);
            self::assertSame($expected['max'], $definition['key_count_max']);
            self::assertCount($expected['locales'], $definition['locales']);

            $referencedFiles = [];
            $payloadCache = [];
            $checkedPayloads = [];
            $contentHashes = [];

            foreach ($definition['locales'] as $locale => $canonicalLocale) {
                $path = sprintf(
                    '%s/data/%s/%s.php',
                    $root,
                    $definition['directory'],
                    $canonicalLocale
                );
                self::assertFileExists($path, $type.'/'.$locale);
                self::assertSame($canonicalLocale, $definition['locales'][$canonicalLocale]);
                $payloadCache[$canonicalLocale] ??= require $path;
                $payload = $payloadCache[$canonicalLocale];
                $keySetId = $contract['types'][$type]['locale_key_sets'][$locale];
                $expectedKeys = $contract['types'][$type]['key_sets'][$keySetId];
                self::assertSame($expectedKeys, array_keys($payload), $type.'/'.$locale);
                $referencedFiles[realpath($path)] = true;

                if (!isset($checkedPayloads[$canonicalLocale])) {
                    $contentHash = hash('sha256', serialize($payload));
                    self::assertArrayNotHasKey($contentHash, $contentHashes);
                    $contentHashes[$contentHash] = $canonicalLocale;
                    $checkedPayloads[$canonicalLocale] = true;
                }
            }

            $storedFiles = glob($root.'/data/'.$definition['directory'].'/*.php') ?: [];
            self::assertCount(count($referencedFiles), $storedFiles);

            foreach ($storedFiles as $storedFile) {
                self::assertArrayHasKey(realpath($storedFile), $referencedFiles);
            }
        }
    }

    public function test_repository_exposes_all_locales_and_reuses_identical_payloads(): void
    {
        $root = dirname(__DIR__, 2);
        $repository = new CompiledDataRepository($root.'/data');
        $manifest = require $root.'/data/manifest.php';

        foreach (['countries', 'currencies', 'languages'] as $type) {
            $expectedLocales = array_keys($manifest['types'][$type]['locales']);
            sort($expectedLocales, SORT_STRING);

            self::assertSame($expectedLocales, $repository->locales($type));

            $groups = [];

            foreach ($manifest['types'][$type]['locales'] as $locale => $hash) {
                $groups[$hash][] = $locale;
            }

            $shared = current(array_filter($groups, static fn (array $locales): bool => count($locales) > 1));
            self::assertIsArray($shared);
            self::assertSame(
                $repository->load($type, $shared[0]),
                $repository->load($type, $shared[1])
            );
        }
    }
}
