<?php

/**
 * Data Locale Parser
 *
 * This package uses data from the "umpirsky/currency-list", "umpirsky/language-list"
 * and "umpirsky/country-list" repositories.
 * It was based on the package "tariq86/country-list"
 *
 * @package   Dominservice\DataLocaleParser
 * @author    DSO-IT Mateusz Domin <biuro@dso.biz.pl>
 * @copyright (c) 2021 DSO-IT Mateusz Domin
 * @license   MIT
 * @version   1.3.0
 */

namespace Dominservice\DataLocaleParser;

use Collator;
use RuntimeException;
use Dominservice\DataLocaleParser\Exceptions\CountryNotFoundException;
use Dominservice\DataLocaleParser\Exceptions\CurrencyNotFoundException;
use Dominservice\DataLocaleParser\Exceptions\LanguageNotFoundException;

/**
 * Class DataParser
 * @package Dominservice\DataLocaleParser
 */
class DataParser
{
    /**
     * countries list.
     * @var \Illuminate\Support\Collection
     */
    private $countries;

    /**
     * currencies list.
     * @var \Illuminate\Support\Collection
     */
    private $currencies;

    /**
     * languages list.
     * @var \Illuminate\Support\Collection
     */
    private $languages;

    /**
     * languages list.
     * @var \Illuminate\Support\Collection
     */
    private $fullData;

    /**
     * Path to the directory containing countries data.
     * @var string
     */
    private $countriesDir;

    /**
     * Path to the directory containing currencies data.
     * @var string
     */
    private $currenciesDir;

    /**
     * Path to the directory containing languages data.
     * @var string
     */
    private $languagesDir;

    /**
     * Constructor.
     *
     * @param string|null $dataDir Path to the directory containing countries data
     */
    public function __construct()
    {
        $countriesDir = base_path('vendor/dominservice/data_locale_parser/data/country');

        if (!is_dir($countriesDir)) {
            throw new RuntimeException(sprintf('Unable to locate the country data directory at "%s"', $countriesDir));
        }

        $currenciesDir = base_path('vendor/dominservice/data_locale_parser/data/currency');

        if (!is_dir($currenciesDir)) {
            throw new RuntimeException(sprintf('Unable to locate the country data directory at "%s"', $currenciesDir));
        }

        $languagesDir = base_path('vendor/dominservice/data_locale_parser/data/language');

        if (!is_dir($languagesDir)) {
            throw new RuntimeException(sprintf('Unable to locate the country data directory at "%s"', $languagesDir));
        }

        $this->countriesDir = realpath($countriesDir);
        $this->currenciesDir = realpath($currenciesDir);
        $this->languagesDir = realpath($languagesDir);
    }

    /**
     * Get the country data directory.
     *
     * @return string
     */
    public function getCountriesDir(): string
    {
        return $this->countriesDir;
    }

    /**
     * Get the country data directory.
     *
     * @return string
     */
    public function getCurrenciesDir(): string
    {
        return $this->currenciesDir;
    }

    /**
     * Get the country data directory.
     *
     * @return string
     */
    public function getLanguagesDir(): string
    {
        return $this->languagesDir;
    }

    /**
     * @param string $locale
     * @return \Illuminate\Support\Collection
     */
    public function parseAllDataPerCountry(string $locale = 'en', $country = null)
    {
        if (empty($this->fullData)) {
            $countries = $this->getListCountries($locale);
            $currencies = $this->getListCurrencies($locale);
            $languages = $this->getListLanguages($locale);

            $data = require base_path('vendor/dominservice/data_locale_parser/data/countries_full_data.php');
            $localeToCode = require base_path('vendor/dominservice/data_locale_parser/data/locale_countries_data.php');
            $subdivision = require base_path('vendor/dominservice/data_locale_parser/data/subdivision_iso3166.php');

            foreach ($languages as $code => $lang) {
                if (!empty($localeToCode[$code])) {
                    foreach ($localeToCode[$code] as $k) {
                        if (empty($data[$k]->languages)) {
                            $data[$k]->languages = [];
                        }
                        $data[$k]->languages[$code] = $lang;
                    }
                }
            }
            foreach ($data as $id => &$item) {
                $item->country = $countries[$id];
                $item->currency->name = !empty($currencies[$item->currency->code]) ? $currencies[$item->currency->code] : null;
                $item->subdivision_iso3166 = !empty($subdivision[$item->so]) ? collect($subdivision[$item->so]) : null;
            }
            $this->fullData = $data;
        }

        if ($country && !empty($this->fullData[strtoupper($country)])) {
            return $this->fullData[strtoupper($country)];
        }

        return collect(array_values($this->fullData));
    }

    /**
     * @param string $countryCode
     * @param string $locale
     * @return string
     * @throws CountryNotFoundException
     */
    public function getCountry(string $countryCode, string $locale = 'en'): string
    {
        return $this->getOne('countries', $countryCode, $locale);
    }

    /**
     * @param string $currencyCode
     * @param string $locale
     * @return string
     * @throws CurrencyNotFoundException
     */
    public function getCurrency(string $currencyCode, string $locale = 'en'): string
    {
        return $this->getOne('currencies', $currencyCode, $locale);
    }

    /**
     * @param string $langCode
     * @param string $locale
     * @return string
     * @throws LanguageNotFoundException
     */
    public function getLanguage(string $langCode, string $locale = 'en'): string
    {
        return $this->getOne('languages', $langCode, $locale);
    }

    /**
     * @param string $locale
     * @param bool $sorted
     * @return \Illuminate\Support\Collection
     */
    public function getListCountries(string $locale = 'en', bool $sorted = true): \Illuminate\Support\Collection
    {
        return collect((array)$this->getList('countries',  $locale, $sorted));
    }

    /**
     * @param string $locale
     * @param bool $sorted
     * @return \Illuminate\Support\Collection
     */
    public function getListCurrencies(string $locale = 'en', bool $sorted = true): \Illuminate\Support\Collection
    {
        return collect((array)$this->getList('currencies',  $locale, $sorted));
    }

    /**
     * @param string $locale
     * @param bool $sorted
     * @return \Illuminate\Support\Collection
     */
    public function getListLanguages(string $locale = 'en', bool $sorted = true): \Illuminate\Support\Collection
    {
        return collect((array)$this->getList('languages',  $locale, $sorted));
    }

    /**
     * @param string $type
     * @param string $id
     * @param string $locale
     * @return string
     * @throws CountryNotFoundException
     * @throws CurrencyNotFoundException
     * @throws LanguageNotFoundException
     */
    public function getOne(string $type, string $id, string $locale = 'en'): string
    {
        $id = mb_strtoupper($id);
        $locales = $this->loadData($type, $locale, false);

        if (!$this->has($type, $id, $locale)) {
            if ($type === 'countries') {
                throw new CountryNotFoundException($id);
            } elseif ($type === 'currencies') {
                throw new CurrencyNotFoundException($id);
            } elseif ($type === 'languages') {
                throw new LanguageNotFoundException($id);
            } else {
                throw new \Exception('Incorrect data type selected');
            }
        }

        return $locales[mb_strtoupper($id)];
    }

    /**
     * Returns a list.
     *
     * @param string $type The type data
     * @param string $locale The locale (default: en)
     * @param bool $sorted Sort the list? (default: true)
     * @return mixed         An array (list) with country or raw data
     */
    public function getList(string $type, string $locale = 'en', bool $sorted = true): array
    {
        return $this->loadData($type, $locale, $sorted);
    }

    /**
     * @param string $type The type data
     * @param string $locale The locale
     * @param array $data An array (list) with country data
     * @return DataParser   The instance of DataParser to enable fluent interface
     */
    public function setList(string $type, string $locale, array $data): DataParser
    {
        $this->{$type}[$locale] = $data;
        return $this;
    }

    /**
     * A lazy-loader that loads data from a PHP file if it is not stored in memory yet.
     *
     * @param string $type The type data
     * @param string $locale The locale
     * @param bool $sorted Should we sort the country list? (default: true)
     * @return mixed         An array (list) with country or raw data
     */
    protected function loadData(string $type, string $locale, bool $sorted = true): array
    {
        $locale = str_replace('-', '_', $locale);

        if (!isset($this->{$type}[$locale])) {
            // Customization - "source" does not matter anymore because umpirsky refactored his library.
            if ($type === 'countries') {$text = 'country';}
            elseif ($type === 'currencies') {$text = 'currency';}
            elseif ($type === 'languages') {$text = 'language';}
            else {$text = '__';}

            $file = sprintf('%s/%s/'.$text.'.php', $this->{$type.'Dir'}, $locale);

            if (!is_file($file)) {
                throw new RuntimeException(sprintf('Unable to load the country data file "%s"', $file));
            }

            $this->{$type}[$locale] = require $file;
        }
        if ($sorted) {
            return $this->sortData($locale, $this->{$type}[$locale]);
        }
        return $this->{$type}[$locale];
    }

    /**
     * Sorts the data array for a given locale, using the locale translations.
     * It is UTF-8 aware if the Collator class is available (requires the intl
     * extension).
     *
     * @param string $locale The locale whose collation rules should be used.
     * @param mixed $data Array of strings or raw data.
     * @return mixed         If $data is an array, it will be sorted, otherwise raw data
     */
    protected function sortData(string $locale, $data): array
    {
        if (is_array($data)) {
            $collator = new Collator($locale);
            $collator->asort($data);
        }
        return $data;
    }

    /**
     * Indicates whether or not a given $id matches a country.
     *
     * @param string $type
     * @param string $id
     * @param string $locale The locale (default: en)
     * @return bool                <code>true</code> if a match was found, <code>false</code> otherwise
     */
    public function has(string $type, string $id, string $locale = 'en'): bool
    {
        $locales = $this->loadData($type, $locale, false);
        return isset($locales[mb_strtoupper($id)]);
    }

}