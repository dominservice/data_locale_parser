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

namespace Dominservice\DataLocaleParser\Exceptions;

/**
 * Class CurrencyNotFoundException
 * @package   Dominservice\DataLocaleParser
 */
class CurrencyNotFoundException extends \Exception
{
    /**
     * Constructor.
     *
     * @param string $currencyCode
     */
    public function __construct($currencyCode)
    {
        parent::__construct("Currency '{$currencyCode}' not found.");
    }
}
