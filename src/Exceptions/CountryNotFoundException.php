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

namespace Dominservice\DataLocaleParser\Exceptions;

/**
 * Class CountryNotFoundException
 * @package Dominservice\DataLocaleParser
 */
class CountryNotFoundException extends \Exception
{
    /**
     * Constructor.
     *
     * @param string $countryCode A 2-letter country code
     */
    public function __construct($countryCode)
    {
        parent::__construct("Country '{$countryCode}' not found.");
    }
}
