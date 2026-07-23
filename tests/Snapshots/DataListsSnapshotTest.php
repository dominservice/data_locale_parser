<?php

declare(strict_types=1);

namespace Dominservice\DataLocaleParser\Tests\Snapshots;

use Dominservice\DataLocaleParser\DataParser;
use Dominservice\DataLocaleParser\Tests\Support\InteractsWithSnapshots;
use Dominservice\DataLocaleParser\Tests\TestCase;

final class DataListsSnapshotTest extends TestCase
{
    use InteractsWithSnapshots;

    private const REPRESENTATIVE_LOCALES = [
        'en',
        'en_US',
        'en_GB',
        'pl',
        'pl_PL',
        'de_CH',
        'fr_CA',
        'es_MX',
        'pt_BR',
        'ar',
        'ar_EG',
        'he',
        'fa',
        'ur',
        'hi',
        'ja',
        'zh_Hans',
        'zh_Hant',
        'ru',
        'uk',
        'sr_Latn',
        'sw',
        'as',
        'eo',
        'tl',
        'gv',
        'kw',
    ];

    private const TYPES = [
        'countries' => 'country',
        'currencies' => 'currency',
        'languages' => 'language',
    ];

    public function test_representative_complete_lists_match_snapshot(): void
    {
        $parser = $this->app->make(DataParser::class);
        $snapshot = [
            'schema' => 1,
            'locales' => self::REPRESENTATIVE_LOCALES,
            'types' => [],
        ];

        foreach (self::TYPES as $type => $directory) {
            foreach (self::REPRESENTATIVE_LOCALES as $locale) {
                $snapshot['types'][$type][$locale] = [
                    'raw' => $parser->getList($type, $locale, false),
                ];
            }
        }

        $this->assertMatchesJsonSnapshot('data/representative-lists.json', $snapshot);
    }

    public function test_every_available_locale_matches_exhaustive_fingerprint_snapshot(): void
    {
        $parser = $this->app->make(DataParser::class);
        $snapshot = [
            'schema' => 1,
            'types' => [],
        ];

        foreach (self::TYPES as $type => $directory) {
            $manifest = require dirname(__DIR__, 2).'/data/manifest.php';
            $locales = array_keys($manifest['types'][$type]['locales']);
            sort($locales);

            $typeSnapshot = [
                'locale_count' => count($locales),
                'locales' => [],
            ];

            foreach ($locales as $locale) {
                $raw = $parser->getList($type, $locale, false);

                $typeSnapshot['locales'][$locale] = [
                    'count' => count($raw),
                    'raw_sha256' => $this->snapshotHash($raw),
                    'first_raw_key' => array_key_first($raw),
                    'last_raw_key' => array_key_last($raw),
                ];
            }

            $snapshot['types'][$type] = $typeSnapshot;
        }

        $this->assertMatchesJsonSnapshot('data/all-locales-fingerprints.json', $snapshot);
    }
}
