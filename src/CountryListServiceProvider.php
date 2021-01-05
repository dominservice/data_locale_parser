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

namespace Dominservice\DataParser;

use Illuminate\Support\ServiceProvider;

/**
 * CountryListServiceProvider
 *
 * @author Tariq86
 */
class CountryListServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('countryList', function () {
            return new CountryList;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['countrylist'];
    }

}
