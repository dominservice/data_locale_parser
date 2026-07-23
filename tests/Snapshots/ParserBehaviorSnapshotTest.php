<?php

declare(strict_types=1);

namespace Dominservice\DataLocaleParser\Tests\Snapshots;

use Dominservice\DataLocaleParser\DataParser;
use Dominservice\DataLocaleParser\Tests\Support\InteractsWithSnapshots;
use Dominservice\DataLocaleParser\Tests\Support\NormalizesSnapshotValues;
use Dominservice\DataLocaleParser\Tests\TestCase;
use Throwable;

final class ParserBehaviorSnapshotTest extends TestCase
{
    use InteractsWithSnapshots;
    use NormalizesSnapshotValues;

    public function test_public_parser_behavior_matches_snapshot(): void
    {
        $parser = $this->app->make(DataParser::class);
        $snapshot = [
            'schema' => 1,
            'directories' => [
                'countries' => $this->directoryDescriptor($parser->getCountriesDir()),
                'currencies' => $this->directoryDescriptor($parser->getCurrenciesDir()),
                'languages' => $this->directoryDescriptor($parser->getLanguagesDir()),
            ],
            'single_values' => [
                'countries' => $this->countryExamples($parser),
                'currencies' => $this->currencyExamples($parser),
                'languages' => $this->languageExamples($parser),
            ],
            'normalization' => [
                'pt-BR_equals_pt_BR' => $parser->getList('countries', 'pt-BR', false)
                    === $parser->getList('countries', 'pt_BR', false),
                'lowercase_country_code' => $parser->getCountry('pl', 'pl'),
                'lowercase_currency_code' => $parser->getCurrency('pln', 'pl'),
            ],
            'has' => [
                'country_pl' => $parser->has('countries', 'pl', 'en'),
                'country_invalid' => $parser->has('countries', 'ZZ', 'en'),
                'currency_pln' => $parser->has('currencies', 'pln', 'en'),
                'currency_invalid' => $parser->has('currencies', 'ZZZ', 'en'),
                'language_pl' => $parser->has('languages', 'pl', 'en'),
                'language_uppercase_is_case_sensitive' => $parser->has('languages', 'PL', 'en'),
            ],
            'exceptions' => [
                'country' => $this->captureException(fn () => $parser->getCountry('ZZ', 'en')),
                'currency' => $this->captureException(fn () => $parser->getCurrency('ZZZ', 'en')),
                'language' => $this->captureException(fn () => $parser->getLanguage('__missing__', 'en')),
                'locale' => $this->captureException(fn () => $parser->getListCountries('__missing__')),
                'type' => $this->captureException(fn () => $parser->getList('__missing__', 'en')),
            ],
            'custom_lists' => $this->customListBehavior(),
            'addresses' => $this->addressExamples($parser),
        ];

        $this->assertMatchesJsonSnapshot('parser/public-behavior.json', $snapshot);
    }

    public function test_full_country_data_matches_snapshot(): void
    {
        $snapshot = [
            'schema' => 1,
            'locales' => [],
        ];

        foreach (['en', 'pl', 'de', 'ja', 'ar'] as $locale) {
            $parser = new DataParser();
            $all = $parser->parseAllDataPerCountry($locale);
            $normalizedAll = $this->normalizeSnapshotValue($all, true);

            $snapshot['locales'][$locale] = [
                'collection_type' => $all::class,
                'count' => $all->count(),
                'sha256' => $this->snapshotHash($normalizedAll),
                'countries' => [],
            ];

            foreach (['PL', 'US', 'JP', 'AE', 'BR'] as $countryCode) {
                $countryParser = new DataParser();
                $country = $countryParser->parseAllDataPerCountry($locale, $countryCode);
                $snapshot['locales'][$locale]['countries'][$countryCode] =
                    $this->normalizeSnapshotValue($country, true);
            }
        }

        $cachedParser = new DataParser();
        $firstLocale = $cachedParser->parseAllDataPerCountry('pl', 'PL');
        $secondLocale = $cachedParser->parseAllDataPerCountry('en', 'PL');
        $invalidCountry = (new DataParser())->parseAllDataPerCountry('en', 'ZZ');
        $snapshot['cache_and_country_argument'] = [
            'first_pl_country' => $firstLocale->country,
            'second_en_country_on_same_instance' => $secondLocale->country,
            'lowercase_country' => (new DataParser())->parseAllDataPerCountry('en', 'pl')->country,
            'invalid_country_returns' => [
                'type' => $invalidCountry::class,
                'count' => $invalidCountry->count(),
            ],
        ];

        $this->assertMatchesJsonSnapshot('parser/full-country-data.json', $snapshot);
    }

    public function test_full_language_data_matches_snapshot(): void
    {
        $parser = $this->app->make(DataParser::class);
        $all = $parser->getLanguagesFullData(null, 'en', false);
        $regional = $parser->getLanguagesFullData(null, 'pl', false, ['regional' => true]);
        $selectedRegional = $parser->getLanguagesFullData(
            null,
            'pl',
            false,
            ['regional' => ['en_US', 'pl_PL', 'pt_BR', 'zh_Hant']]
        );
        $latin = $parser->getLanguagesFullData(null, 'de', false, ['script' => ['Latn']]);

        $snapshot = [
            'schema' => 1,
            'all' => [
                'type' => $all::class,
                'count' => $all->count(),
                'sha256' => $this->snapshotHash($this->normalizeSnapshotValue($all)),
                'first_key' => $all->keys()->first(),
                'last_key' => $all->keys()->last(),
            ],
            'selected' => $this->normalizeSnapshotValue(
                $parser->getLanguagesFullData(
                    ['en', 'en_US', 'pl_PL', 'zh_Hant', 'sr_Latn', 'ar'],
                    'pl',
                    false
                )
            ),
            'single' => [
                'pl_PL' => $parser->getLanguageFullData('pl_PL', 'en'),
                'zh_Hant' => $parser->getLanguageFullData('zh-Hant', 'pl'),
                'missing' => $parser->getLanguageFullData('__missing__', 'en'),
            ],
            'missing_display_locale' => $this->normalizeSnapshotValue(
                $parser->getLanguagesFullData(['pl-PL'], '__missing__', false)
            ),
            'filters' => [
                'regional_boolean' => [
                    'count' => $regional->count(),
                    'sha256' => $this->snapshotHash($this->normalizeSnapshotValue($regional)),
                ],
                'regional_codes' => $this->normalizeSnapshotValue($selectedRegional),
                'latin' => [
                    'count' => $latin->count(),
                    'sha256' => $this->snapshotHash($this->normalizeSnapshotValue($latin)),
                ],
            ],
        ];

        $this->assertMatchesJsonSnapshot('parser/full-language-data.json', $snapshot);
    }

    /**
     * @return array<string, mixed>
     */
    private function countryExamples(DataParser $parser): array
    {
        return [
            'US/en' => $parser->getCountry('US', 'en'),
            'PL/pl' => $parser->getCountry('PL', 'pl'),
            'DE/de' => $parser->getCountry('DE', 'de'),
            'JP/ja' => $parser->getCountry('JP', 'ja'),
            'EG/ar' => $parser->getCountry('EG', 'ar'),
            'CN/zh_Hans' => $parser->getCountry('CN', 'zh_Hans'),
            'IN/hi' => $parser->getCountry('IN', 'hi'),
            'UA/uk' => $parser->getCountry('UA', 'uk'),
            'BR/pt_BR' => $parser->getCountry('BR', 'pt_BR'),
            'RS/sr_Latn' => $parser->getCountry('RS', 'sr_Latn'),
            'AF/as' => $parser->getCountry('AF', 'as'),
            'PH/tl' => $parser->getCountry('PH', 'tl'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function currencyExamples(DataParser $parser): array
    {
        return [
            'USD/en' => $parser->getCurrency('USD', 'en'),
            'PLN/pl' => $parser->getCurrency('PLN', 'pl'),
            'EUR/de' => $parser->getCurrency('EUR', 'de'),
            'JPY/ja' => $parser->getCurrency('JPY', 'ja'),
            'EGP/ar' => $parser->getCurrency('EGP', 'ar'),
            'CNY/zh_Hans' => $parser->getCurrency('CNY', 'zh_Hans'),
            'INR/hi' => $parser->getCurrency('INR', 'hi'),
            'UAH/uk' => $parser->getCurrency('UAH', 'uk'),
            'BRL/pt_BR' => $parser->getCurrency('BRL', 'pt_BR'),
            'RSD/sr_Latn' => $parser->getCurrency('RSD', 'sr_Latn'),
            'AFN/as' => $parser->getCurrency('AFN', 'as'),
            'AFN/eo' => $parser->getCurrency('AFN', 'eo'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function languageExamples(DataParser $parser): array
    {
        return [
            'pl/en' => $parser->getLanguage('pl', 'en'),
            'pl/pl' => $parser->getLanguage('pl', 'pl'),
            'de/de' => $parser->getLanguage('de', 'de'),
            'ja/ja' => $parser->getLanguage('ja', 'ja'),
            'ar/ar' => $parser->getLanguage('ar', 'ar'),
            'zh/zh_Hans' => $parser->getLanguage('zh', 'zh_Hans'),
            'hi/hi' => $parser->getLanguage('hi', 'hi'),
            'uk/uk' => $parser->getLanguage('uk', 'uk'),
            'pt_BR/pt_BR' => $parser->getLanguage('pt_BR', 'pt_BR'),
            'sr/sr_Latn' => $parser->getLanguage('sr', 'sr_Latn'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function customListBehavior(): array
    {
        $parser = new DataParser();
        $returnValue = $parser->setList('countries', 'x_test', [
            'AA' => 'Zulu',
            'BB' => 'Alpha',
        ]);

        return [
            'fluent_return' => $returnValue === $parser,
            'raw' => $parser->getList('countries', 'x_test', false),
            'sorted' => $parser->getList('countries', 'x_test', true),
            'one' => $parser->getCountry('aa', 'x_test'),
            'has' => $parser->has('countries', 'bb', 'x_test'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function addressExamples(DataParser $parser): array
    {
        return [
            'us' => $parser->formatAddress([
                'address' => '123 Main Street',
                'address2' => 'Suite 4',
                'city' => 'New York',
                'subdivision' => 'NY',
                'postalCode' => '10001',
                'countryCode' => 'US',
            ], 'Jane Doe', 'Example Inc.', 'US123', '+1 555 0100', ['Customer 42']),
            'dk' => $parser->formatAddress([
                'address' => 'Testvej 1',
                'city' => 'København',
                'postalCode' => '2100',
                'countryCode' => 'DK',
            ]),
            'international' => $parser->formatAddress([
                'address' => 'ul. Testowa 1',
                'city' => 'Warszawa',
                'subdivision' => 'Mazowieckie',
                'postalCode' => '00-001',
                'countryCode' => 'PL',
            ]),
            'missing_country' => $this->captureException(fn () => $parser->formatAddress([
                'address' => 'Missing country',
            ])),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function directoryDescriptor(string $path): array
    {
        return [
            'exists' => is_dir($path),
            'basename' => basename($path),
            'parent_basename' => basename(dirname($path)),
        ];
    }

    /**
     * @return array{class: class-string<Throwable>, message: string, code: int}
     */
    private function captureException(callable $callback): array
    {
        try {
            $callback();
        } catch (Throwable $exception) {
            $packageRoot = realpath(dirname(__DIR__, 2));
            $message = $exception->getMessage();

            if ($packageRoot !== false) {
                $message = str_replace($packageRoot, '<package>', $message);
            }

            return [
                'class' => $exception::class,
                'message' => $message,
                'code' => $exception->getCode(),
            ];
        }

        self::fail('Expected callback to throw an exception.');
    }
}
