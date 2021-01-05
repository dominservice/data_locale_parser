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
 * @version   1.0.0
 */

namespace Dominservice\DataLocaleParser\Fasade;

use Illuminate\Support\Facades\Facade;

/**
 * Class DataParserFacade
 * @package Dominservice\DataLocaleParser
 *
 * @method static string getCountriesDir()
 * @method static string getCurrenciesDir()
 * @method static string getLanguagesDir()
 * @method static string getCountry(string $countryCode, string $locale = 'en')
 * @method static string getCurrency(string $currencyCode, string $locale = 'en')
 * @method static string getLanguage(string $langCode, string $locale = 'en')
 * @method static string getOne(string $type, string $id, string $locale = 'en')
 * @method static array getListCountries(string $locale = 'en', string $format = 'php')
 * @method static array getListCurrencies(string $locale = 'en', string $format = 'php')
 * @method static array getListLanguages(string $locale = 'en', string $format = 'php')
 * @method static array getList(string $type, string $locale = 'en', string $format = 'php')
 * @method static \Dominservice\DataLocaleParser\DataParser setList(string $type, string $locale, array $data)
 * @method static bool has(string $type, string $countryCode, string $locale = 'en')
 */
class DataParserFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return \Dominservice\DataLocaleParser\DataParser::class;
    }

}
