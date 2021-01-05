<?php

/**
 * Data Locale Parser
 *
 * This package uses data from the "umpirsky/currency-list", "umpirsky/language-list"
 * and "umpirsky/country-list" repositories.
 * It was based on the package "tariq86/country-list"
 *
 * @package   Dominservice\DataParser
 * @author    DSO-IT Mateusz Domin <biuro@dso.biz.pl>
 * @copyright (c) 2021 DSO-IT Mateusz Domin
 * @license   MIT
 * @version   1.0.0
 */

namespace Dominservice\DataParser\Exceptions;

/**
 * Class LanguageNotFoundException
 * @package Dominservice\DataParser
 */
class LanguageNotFoundException extends \Exception
{
    /**
     * Constructor.
     *
     * @param string $langCode
     */
    public function __construct($langCode)
    {
        parent::__construct("Language '{$langCode}' not found.");
    }
}
