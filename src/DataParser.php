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
 * @version   1.4.0
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

            // Get available address formats from the package
            $formats = new \ReflectionClass('\AddressFormat\Formats');
            $addressFormats = [];
            foreach ($formats->getMethods(\ReflectionMethod::IS_PUBLIC | \ReflectionMethod::IS_STATIC) as $method) {
                if (strpos($method->name, 'country') === 0) {
                    $countryCode = substr($method->name, 7); // Remove 'country' prefix
                    $addressFormats[strtoupper($countryCode)] = true;
                }
            }

            foreach ($languages as $code => $lang) {
                if (!empty($localeToCode[$code])) {
                    foreach ($localeToCode[$code] as $k) {
                        if (isset($data[$k]) && !isset($data[$k]->languages)) {
                            $data[$k]->languages = [];
                        }
                        if (isset($data[$k]->languages)) {
                            $data[$k]->languages[$code] = $lang;
                        }
                    }
                }
            }
            foreach ($data as $id => &$item) {
                $item->country = $countries[$id];
                $item->currency->name = !empty($currencies[$item->currency->code]) ? $currencies[$item->currency->code] : null;
                $item->subdivision_iso3166 = !empty($subdivision[$item->so]) ? collect($subdivision[$item->so]) : null;
                
                // Add address format information
                $item->address_format = isset($addressFormats[$id]) ? $id : 'INTERNATIONAL';
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
     * Cached full language data
     * @var \Illuminate\Support\Collection
     */
    private $languagesFullData;

    /**
     * Get full language data including script, native name, and regional code
     * 
     * @param array|string|null $locales Filter languages by these locale codes
     * @param string|null $displayLocale Locale to use for language names (defaults to current app locale)
     * @param bool $sorted Sort the list? (default: true)
     * @param array $filters Additional filters (script, regional, etc.)
     * @return \Illuminate\Support\Collection
     */
    public function getLanguagesFullData($locales = null, ?string $displayLocale = null, bool $sorted = true, array $filters = []): \Illuminate\Support\Collection
    {
        // Load data from cache if available
        if (!$this->languagesFullData) {
            $this->languagesFullData = collect(require base_path('vendor/dominservice/data_locale_parser/data/languages_full_data.php'));
        }

        $data = $this->languagesFullData;

        // Apply locale filtering
        if ($locales) {
            $locales = is_string($locales) ? [$locales] : $locales;

            // Convert locales to use hyphens instead of underscores for comparison
            $normalizedLocales = array_map(function($locale) {
                return str_replace('_', '-', $locale);
            }, $locales);

            $data = $data->filter(function ($item, $key) use ($normalizedLocales) {
                return in_array($key, $normalizedLocales);
            });
        }

        // Apply additional filters
        if (!empty($filters)) {
            $data = $data->filter(function ($item) use ($filters) {
                foreach ($filters as $key => $value) {
                    // Skip if the filter key doesn't exist in the item
                    if (!isset($item[$key])) {
                        continue;
                    }

                    // If the filter value is an array, check if the item value is in the array
                    if (is_array($value)) {
                        if (!in_array($item[$key], $value)) {
                            return false;
                        }
                    } 
                    // Otherwise, check if the item value equals the filter value
                    else if ($item[$key] !== $value) {
                        return false;
                    }
                }
                return true;
            });
        }

        // Determine which locale to use for language names
        $displayLocale = $displayLocale ?: app()->currentLocale();
        $fallbackLocale = 'en'; // Default fallback locale

        // Load language names for the specified locale
        try {
            $languageNames = $this->getListLanguages($displayLocale);
            $hasFallback = false;

            // If we couldn't get language names for the requested locale, try the fallback
            if ($languageNames->isEmpty() && $displayLocale !== $fallbackLocale) {
                $languageNames = $this->getListLanguages($fallbackLocale);
                $hasFallback = true;
            }

            // Update the 'name' field with the name in the specified locale
            $data = $data->map(function ($item, $key) use ($languageNames, $hasFallback) {
                // Extract the base language code (without region)
                $baseCode = explode('-', $key)[0];

                // If we have a translation for this language in the current locale, use it
                if ($languageNames->has($baseCode)) {
                    $item['name'] = $languageNames[$baseCode];

                    // Add a flag indicating if we're using fallback translation
                    if ($hasFallback) {
                        $item['using_fallback_name'] = true;
                    }
                }

                return $item;
            });
        } catch (\Exception $e) {
            // If there's an error loading the language names, just continue with the original data
        }

        // Sort the data if requested
        if ($sorted && $data->count() > 0) {
            $data = $this->sortLanguagesFullData($data, $displayLocale);
        }

        return $data;
    }

    /**
     * Get full data for a single language
     *
     * @param string $code Language code
     * @param string|null $displayLocale Locale to use for language name (defaults to current app locale)
     * @return array|null Language data or null if not found
     */
    public function getLanguageFullData(string $code, ?string $displayLocale = null): ?array
    {
        $code = str_replace('_', '-', $code);
        $result = $this->getLanguagesFullData([$code], $displayLocale, false);

        return $result->has($code) ? $result->get($code) : null;
    }

    /**
     * Sort the full language data collection
     *
     * @param \Illuminate\Support\Collection $data The language data collection
     * @param string $locale The locale to use for sorting
     * @return \Illuminate\Support\Collection Sorted collection
     */
    protected function sortLanguagesFullData(\Illuminate\Support\Collection $data, string $locale): \Illuminate\Support\Collection
    {
        // Extract to array for sorting
        $array = $data->all();

        // Create a collator for proper locale-aware sorting
        $collator = new Collator($locale);

        // Sort by name
        uasort($array, function ($a, $b) use ($collator) {
            return $collator->compare($a['name'], $b['name']);
        });

        // Return as collection
        return collect($array);
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
        $id = $type!='languages' ? mb_strtoupper($id) : $id;
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

        return $locales[$id];
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
        $id = $type!='languages' ? mb_strtoupper($id) : $id;
        $locales = $this->loadData($type, $locale, false);
        return isset($locales[$id]);
    }

    /**
     * Format an address based on country-specific format
     *
     * @param array $addressData Address data with keys like address, address2, city, subdivision, postalCode, countryCode
     * @param string|null $phoneNumber Optional phone number to include in the formatted address
     * @return array Formatted address as an array of lines
     */
    public function formatAddress(array $addressData, ?string $phoneNumber = null): array
    {
        // Ensure countryCode is set
        if (!isset($addressData['countryCode'])) {
            throw new \InvalidArgumentException('Country code is required for address formatting');
        }

        // Create a new instance of AddressFormat
        $formatter = new \AddressFormat\AddressFormat();
        
        // Get formatted address lines
        $result = $formatter->format($addressData);
        
        // Ensure we have an array
        $addressLines = is_array($result) ? $result : [$result];
        
        // Add phone number if provided
        if ($phoneNumber !== null && $phoneNumber !== '') {
            $addressLines = array_merge($addressLines, [$phoneNumber]);
        }
        
        return $addressLines;
    }

}
