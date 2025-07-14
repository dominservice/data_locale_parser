<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Language Detection Settings
    |--------------------------------------------------------------------------
    |
    | These options configure the behavior of the language detection system.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Detect Language from URL
    |--------------------------------------------------------------------------
    |
    | If true, the middleware will attempt to detect the language from the URL.
    | For example, if the URL is example.com/en/page, the language will be set to 'en'.
    |
    */
    'detect_from_url' => true,

    /*
    |--------------------------------------------------------------------------
    | Use Cookies for Language Storage
    |--------------------------------------------------------------------------
    |
    | If true, the middleware will store the language preference in a cookie.
    | This allows the language preference to persist across requests without
    | showing it in the URL for non-API routes.
    |
    */
    'use_cookies' => true,

    /*
    |--------------------------------------------------------------------------
    | Detect Language from Header
    |--------------------------------------------------------------------------
    |
    | If true, the middleware will attempt to detect the language from the request header.
    | This is used as a fallback if URL detection is disabled or fails.
    |
    */
    'detect_from_header' => true,

    /*
    |--------------------------------------------------------------------------
    | Header Name
    |--------------------------------------------------------------------------
    |
    | The name of the header to check for the language code.
    |
    */
    'header_name' => 'Accept-Language',

    /*
    |--------------------------------------------------------------------------
    | Default Locale
    |--------------------------------------------------------------------------
    |
    | The default locale to use if no language is detected.
    |
    */
    'default_locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Allowed Locales
    |--------------------------------------------------------------------------
    |
    | The list of locales that are allowed to be used.
    | If a language is detected that is not in this list, the default locale will be used.
    |
    */
    'allowed_locales' => [
        'en',
        'pl',
        'de',
        'fr',
        'es',
        'en_GB',
        'en_US',
    ],

    /*
    |--------------------------------------------------------------------------
    | RTL Locales
    |--------------------------------------------------------------------------
    |
    | The list of locales that use Right-to-Left (RTL) text direction.
    | This is used by the locale_is_rtl() helper function.
    |
    */
    'locale_rtl' => [
        'ar',    // Arabic
        'fa',    // Persian (Farsi)
        'he',    // Hebrew
        'ur',    // Urdu
        'yi',    // Yiddish
        'dv',    // Divehi
        'ha',    // Hausa
        'khw',   // Khowar
        'ks',    // Kashmiri
        'ku',    // Kurdish
        'ps',    // Pashto
        'sd',    // Sindhi
        'ug',    // Uyghur
    ],

    /*
    |--------------------------------------------------------------------------
    | API Prefixes
    |--------------------------------------------------------------------------
    |
    | The list of prefixes that are used for API routes.
    | If a URL starts with one of these prefixes, the language will be detected
    | from the next segment of the URL.
    | For example, if the URL is example.com/api/en/endpoint, the language will be set to 'en'.
    |
    */
    'api_prefixes' => [
        'api',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cookie Settings
    |--------------------------------------------------------------------------
    |
    | These options configure how cookies are used for language storage.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Cookie Name
    |--------------------------------------------------------------------------
    |
    | The name of the cookie used to store the language preference.
    |
    */
    'cookie_name' => 'language',

    /*
    |--------------------------------------------------------------------------
    | Cookie Lifetime
    |--------------------------------------------------------------------------
    |
    | The lifetime of the cookie in minutes. Default is 43200 (30 days).
    |
    */
    'cookie_lifetime' => 43200, // 30 days

    /*
    |--------------------------------------------------------------------------
    | Language Change Route
    |--------------------------------------------------------------------------
    |
    | The route used for changing the language. This route will be registered
    | automatically by the service provider.
    | For example, if set to 'change-language', the route will be:
    | example.com/change-language/{language}
    |
    */
    'language_change_route' => 'change-language',
];
