<?php

/**
 * This example demonstrates how to use the language handling middleware
 * to automatically detect and set the application locale based on URL paths
 * and request headers.
 */

// Example 1: Basic usage in routes/web.php
Route::middleware(['language'])->group(function () {
    Route::get('/', 'HomeController@index');
    Route::get('/{any}', 'HomeController@index')->where('any', '.*');
});

// Example 2: API routes with language in the second segment
Route::prefix('api')->middleware(['language'])->group(function () {
    Route::get('/{lang}/users', 'Api\UserController@index');
    Route::get('/{lang}/posts', 'Api\PostController@index');
});

// Example 3: Using the middleware with route parameters
Route::get('/{lang}/products/{id}', 'ProductController@show')
    ->middleware('language')
    ->where('lang', '[a-z]{2}|[a-z]{2}_[A-Z]{2}');

// Example 4: Accessing the current locale in a controller
class ExampleController extends Controller
{
    public function index()
    {
        $locale = app()->getLocale();

        return view('welcome', [
            'locale' => $locale,
            'message' => __('messages.welcome'), // Translate using the detected locale
        ]);
    }
}

// Example 5: Custom configuration in config/data_locale_parser.php
/*
return [
    'detect_from_url' => true,
    'detect_from_header' => true,
    'header_name' => 'X-Language', // Custom header name
    'default_locale' => 'en',
    'allowed_locales' => [
        'en',
        'pl',
        'de',
        'fr',
        'es',
        'en_GB',
        'en_US',
        'ja',
        'zh_CN',
    ],
    'api_prefixes' => [
        'api',
        'api/v1',
        'api/v2',
    ],
];
*/

// Example of how to access the configuration in your code:
// $config = config('data_locale_parser');

// Example of accessing configuration in a controller:
class ConfigExampleController extends Controller
{
    public function index()
    {
        $config = config('data_locale_parser');
        $defaultLocale = $config['default_locale'];
        $allowedLocales = $config['allowed_locales'];

        // Use the configuration values
        return view('config-example', [
            'defaultLocale' => $defaultLocale,
            'allowedLocales' => $allowedLocales,
        ]);
    }
}

// Example 6: Registering the middleware in app/Http/Kernel.php
/*
In your app/Http/Kernel.php file:

protected $routeMiddleware = [
    // Other middleware...
    'language' => \Dominservice\DataLocaleParser\Middleware\LanguageMiddleware::class,
];
*/

// Example 7: Using the middleware with a specific locale
/*
In your routes/web.php file:

Route::get('/admin/{any}', 'AdminController@index')
    ->middleware(['language'])
    ->where('any', '.*');
*/
