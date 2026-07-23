<?php

declare(strict_types=1);

namespace Dominservice\DataLocaleParser\Tests\Unit;

use Dominservice\DataLocaleParser\DataParser;
use Dominservice\DataLocaleParser\Exceptions\CountryNotFoundException;
use Dominservice\DataLocaleParser\Exceptions\CurrencyNotFoundException;
use Dominservice\DataLocaleParser\Exceptions\LanguageNotFoundException;
use Dominservice\DataLocaleParser\Tests\TestCase;
use Illuminate\Support\Collection;

final class DataParserContractTest extends TestCase
{
    public function test_list_methods_return_collections_with_exact_raw_payloads(): void
    {
        $parser = $this->app->make(DataParser::class);

        foreach ([
            ['countries', 'country', 'pl_PL', 'getListCountries'],
            ['currencies', 'currency', 'ar_EG', 'getListCurrencies'],
            ['languages', 'language', 'zh_Hant', 'getListLanguages'],
        ] as [$type, $directory, $locale, $method]) {
            $expected = $this->compiledPayload($type, $locale);
            $actual = $parser->{$method}($locale, false);

            self::assertInstanceOf(Collection::class, $actual);
            self::assertSame($expected, $actual->all());
            self::assertSame($expected, $parser->getList($type, $locale, false));
        }
    }

    public function test_hyphenated_locale_is_normalized_without_changing_data(): void
    {
        $parser = $this->app->make(DataParser::class);

        self::assertSame(
            $parser->getList('languages', 'zh_Hant', false),
            $parser->getList('languages', 'zh-Hant', false)
        );
    }

    public function test_sorted_lists_match_the_runtime_collator_for_every_locale(): void
    {
        $parser = $this->app->make(DataParser::class);

        foreach ([
            ['countries', 'country'],
            ['currencies', 'currency'],
            ['languages', 'language'],
        ] as [$type, $directory]) {
            $manifest = require dirname(__DIR__, 2).'/data/manifest.php';
            $locales = array_keys($manifest['types'][$type]['locales']);

            foreach ($locales as $locale) {
                $expected = $this->compiledPayload($type, $locale);
                $collator = new \Collator($locale);
                $collator->asort($expected);

                self::assertSame(
                    $expected,
                    $parser->getList($type, $locale, true),
                    sprintf('Unexpected sorted result for %s/%s.', $type, $locale)
                );
            }
        }
    }

    public function test_service_provider_registers_parser_as_singleton(): void
    {
        self::assertSame(
            $this->app->make(DataParser::class),
            $this->app->make(DataParser::class)
        );
    }

    public function test_sorted_payload_is_cached_and_set_list_invalidates_it(): void
    {
        $parser = new class extends DataParser {
            public int $sortCalls = 0;

            protected function sortData(string $locale, $data): array
            {
                $this->sortCalls++;

                return parent::sortData($locale, $data);
            }
        };

        $first = $parser->getList('countries', 'pl', true);
        $second = $parser->getList('countries', 'pl', true);

        self::assertSame($first, $second);
        self::assertSame(1, $parser->sortCalls);

        $parser->setList('countries', 'pl', ['ZZ' => 'Test']);

        self::assertSame(['ZZ' => 'Test'], $parser->getList('countries', 'pl', true));
        self::assertSame(2, $parser->sortCalls);
    }

    public function test_missing_codes_keep_their_specific_exception_types(): void
    {
        $parser = $this->app->make(DataParser::class);

        foreach ([
            [CountryNotFoundException::class, fn () => $parser->getCountry('ZZ')],
            [CurrencyNotFoundException::class, fn () => $parser->getCurrency('ZZZ')],
            [LanguageNotFoundException::class, fn () => $parser->getLanguage('__missing__')],
        ] as [$expectedClass, $callback]) {
            try {
                $callback();
                self::fail(sprintf('Expected exception "%s" was not thrown.', $expectedClass));
            } catch (\Throwable $exception) {
                self::assertInstanceOf($expectedClass, $exception);
            }
        }
    }

    public function test_every_supported_locale_returns_an_array_with_string_values(): void
    {
        $manifest = require dirname(__DIR__, 2).'/data/manifest.php';
        $localeCount = 0;

        foreach ($manifest['types'] as $type => $definition) {
            foreach (array_keys($definition['locales']) as $locale) {
                $localeCount++;
                $data = $this->compiledPayload($type, $locale);

                self::assertIsArray($data, $type.'/'.$locale);
                self::assertNotEmpty($data, $type.'/'.$locale);

                foreach ($data as $code => $value) {
                    self::assertIsString($code, $type.'/'.$locale);
                    self::assertIsString($value, $type.'/'.$locale);
                }
            }
        }

        self::assertSame(1758, $localeCount);
    }

    /**
     * @return array<string, string>
     */
    private function compiledPayload(string $type, string $locale): array
    {
        $root = dirname(__DIR__, 2);
        $manifest = require $root.'/data/manifest.php';
        $definition = $manifest['types'][$type];
        $canonicalLocale = $definition['locales'][$locale];

        return require sprintf(
            '%s/data/%s/%s.php',
            $root,
            $definition['directory'],
            $canonicalLocale
        );
    }
}
