<?php

/**
 * Data Locale Parser
 *
 * This package uses data from the "umpirsky/currency-list", "umpirsky/language-list"
 * and "umpirsky/country-list" repositories.
 * It was based on the package "tariq86/country-list"
 *
 * @package   Dominservice\CountryList
 * @author    DSO-IT Mateusz Domin <biuro@dso.biz.pl>
 * @copyright (c) 2021 DSO-IT Mateusz Domin
 * @license   MIT
 * @version   1.0.0
 */

if (!function_exists('base_path')) {

    /**
     * Get the path to the root of the codebase.
     *
     * @param  string $path
     * @return string
     */
    function base_path($path = '') {
        $basePath = __DIR__ . DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR;
        return $basePath . $path;
    }
}