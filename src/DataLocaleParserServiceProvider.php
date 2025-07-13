<?php

namespace Dominservice\DataLocaleParser;

use Dominservice\DataLocaleParser\Http\Controllers\LanguageController;
use Dominservice\DataLocaleParser\Http\Middleware\LanguageMiddleware;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class DataLocaleParserServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(DataParser::class, function () {
            return new DataParser();
        });

        $this->mergeConfigFrom(
            __DIR__.'/../config/data_locale_parser.php', 'data_locale_parser'
        );
    }

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        // Publish configuration
        $this->publishes([
            __DIR__ . '/../config/data_locale_parser.php' => config_path('data_locale_parser.php'),
        ], 'config');

        // Register middleware
        $this->app['router']->aliasMiddleware('language', LanguageMiddleware::class);

        // Register routes
        $this->registerRoutes();
    }

    /**
     * Register the package routes.
     *
     * @return void
     */
    protected function registerRoutes()
    {
        // Get the language change route from config
        $routeName = Config::get('data_locale_parser.language_change_route', 'change-language');

        // Register the route
        Route::get($routeName . '/{language}', [LanguageController::class, 'changeLanguage'])
            ->name('language.change');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [DataParser::class];
    }
}
