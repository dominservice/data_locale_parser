# Data Locale Parser

[![Latest Version](https://img.shields.io/github/release/dominservice/data_locale_parser.svg?style=flat-square)](https://github.com/dominservice/data_locale_parser/releases)
[![Total Downloads](https://img.shields.io/packagist/dt/dominservice/data_locale_parser.svg?style=flat-square)](https://packagist.org/packages/dominservice/data_locale_parser)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)

Data Locale Parser is a package for Laravel 5.6 | 5.7 | 5.8 | 6.* | 7.* | 8.* | 9.*| 10.*| 11.*, which lists all countries, currencies and languages, with names and ISO 3166-1 codes in all languages and data formats.

## Installation

Require package via Composer: `composer require dominservice/data_locale_parser`

## Usage

- Locale (en, en_US, fr, fr_CA...)
    - If no locale is given (or if it is set to null), then it will default to 'en'

Declare ussage
```php
use \Dominservice\DataLocaleParser\DataParser;
```
(...)
```php
private $dataParser;
```
(...)
```php
public function __construct() {
    $this->dataParser = new DataParser();
}
```
Get all countries
```php
$this->dataParser->getListCountries('en');
```
Get all currencies
```php
$this->dataParser->getListCurrencies('en');
```
Get all Languages
```php
$this->dataParser->getListLanguages('en');
```

All lists return a collection
___

Get country
```php
$this->dataParser->getCountry('PL', 'en');
```
Get currency
```php
$this->dataParser->getCurrency('PLN', 'en');
```
Get Language
```php
$this->dataParser->geLanguage('pl_PL', 'en');
```

Format an address based on country-specific format
```php
// Address data with keys like address, address2, city, subdivision, postalCode, countryCode
$addressData = [
    'address' => '1234 Some St.',
    'address2' => 'Floor #67',
    'city' => 'San Francisco',
    'subdivision' => 'CA',
    'postalCode' => '94105',
    'countryCode' => 'US'
];

// Basic address formatting (returns an array of address lines)
$formattedAddress = $this->dataParser->formatAddress($addressData);

// Format address with additional parameters
$formattedAddressComplete = $this->dataParser->formatAddress(
    $addressData,                    // Address data
    'John Doe',                      // Name
    'Example Company Ltd.',          // Company name
    'GB123456789',                   // VAT number
    '+1 (123) 456-7890',             // Phone number
    ['Customer ID: 12345']           // Additional fields
);

// Format address with just some parameters (others will be null)
$formattedAddressPartial = $this->dataParser->formatAddress(
    $addressData,                    // Address data
    null,                            // No name
    'Example Company Ltd.',          // Company name
    null,                            // No VAT number
    '+1 (123) 456-7890'              // Phone number
);
```

### Address Format Keys

The following keys can be used in the address data array for the `formatAddress` function:

| Key | Description | Example |
|-----|-------------|---------|
| `address` | Primary address line (street address) | 1234 Some St. |
| `address2` | Secondary address line | Floor #67 |
| `address3` | Tertiary address line | Unit #123 |
| `city` | City name | San Francisco |
| `subdivision` | State, province, or region | CA |
| `postalCode` | Postal or ZIP code | 94105 |
| `countryCode` | ISO 3166-1 Alpha-2 country code (required) | US |

The function will format the address according to the country-specific format based on the `countryCode`. If a specific country format is not available, it will use the international format.

### Additional Address Parameters

The `formatAddress` function accepts several additional parameters beyond the basic address data:

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `name` | string | Person's name to include at the beginning of the address | John Doe |
| `companyName` | string | Company name to include in the address | Example Company Ltd. |
| `vatNumber` | string | VAT identification number | GB123456789 |
| `phoneNumber` | string | Contact phone number | +1 (123) 456-7890 |
| `additionalFields` | array | Array of additional text lines to include | ['Customer ID: 12345'] |

These additional parameters will be included in the formatted address in the following order:
1. Name (if provided)
2. Company name (if provided)
3. VAT number (if provided, prefixed with "VAT: ")
4. Standard address lines (formatted according to country format)
5. Phone number (if provided)
6. Any additional fields (in the order they appear in the array)

If you have collected all data, you make use this code
```php
 $this->dataParser->parseAllDataPerCountry('pl_PL');
```
Then You get 
```php
Illuminate\Support\Collection {▼
  #items: [
    0 => {#1992 ▼
      +"so": "PL"
      +"iso3": "POL"
      +"iso_nr": "616"
      +"fips": "PL"
      +"continent": "EU"
      +"tld": ".pl"
      +"phone": "48"
      +"postal_code_format": "##-###"
      +"postal_code_regex": "^\d{2}-\d{3}$"
      +"currency": {#1991 ▼
        +"name": "złoty polski"
        +"code": "PLN"
        +"symbol": "zł"
      }
      +"languages": array:1 [▼
        "pl" => "polski"
      ]
      +"country": "Polska"
      +"subdivision_iso3166": Illuminate\Support\Collection {#2275 ▼
        #items: array:16 [▼
          "PL-02" => array:2 [▼
            "name" => "Dolnośląskie"
            "name_ascii" => "Dolnoslaskie"
          ]
          "PL-04" => array:2 [▼
            "name" => "Kujawsko-pomorskie"
            "name_ascii" => "Kujawsko-pomorskie"
          ]
          "PL-06" => array:2 [▼
            "name" => "Lubelskie"
            "name_ascii" => "Lubelskie"
          ]
          "PL-08" => array:2 [▼
            "name" => "Lubuskie"
            "name_ascii" => "Lubuskie"
          ]
          "PL-10" => array:2 [▼
            "name" => "Łódzkie"
            "name_ascii" => "Lodzkie"
          ]
          "PL-12" => array:2 [▼
            "name" => "Małopolskie"
            "name_ascii" => "Malopolskie"
          ]
          "PL-14" => array:2 [▼
            "name" => "Mazowieckie"
            "name_ascii" => "Mazowieckie"
          ]
          "PL-16" => array:2 [▼
            "name" => "Opolskie"
            "name_ascii" => "Opolskie"
          ]
          "PL-18" => array:2 [▼
            "name" => "Podkarpackie"
            "name_ascii" => "Podkarpackie"
          ]
          "PL-20" => array:2 [▼
            "name" => "Podlaskie"
            "name_ascii" => "Podlaskie"
          ]
          "PL-22" => array:2 [▼
            "name" => "Pomorskie"
            "name_ascii" => "Pomorskie"
          ]
          "PL-24" => array:2 [▼
            "name" => "Śląskie"
            "name_ascii" => "Slaskie"
          ]
          "PL-26" => array:2 [▶]
          "PL-28" => array:2 [▼
            "name" => "Warmińsko-mazurskie"
            "name_ascii" => "Warminsko-mazurskie"
          ]
          "PL-30" => array:2 [▼
            "name" => "Wielkopolskie"
            "name_ascii" => "Wielkopolskie"
          ]
          "PL-32" => array:2 [▼
            "name" => "Zachodniopomorskie"
            "name_ascii" => "Zachodniopomorskie"
          ]
        ]
      }
    }

    ...
}
```
You may get one country full data 

```php

 $this->dataParser->parseAllDataPerCountry('pl_PL', 'PL');
```

## Language Handling Middleware

This package includes a middleware for handling language detection from URL paths and request headers. This allows you to create routes with language prefixes (e.g., `/en/page`, `/pl/page`) and automatically set the application locale based on the URL or header.

### Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --provider="Dominservice\DataLocaleParser\DataLocaleParserServiceProvider" --tag="config"
```

This will create a `config/data_locale_parser.php` file with the following options:

```php
return [
    // Whether to detect language from URL
    'detect_from_url' => true,

    // Whether to use cookies for language storage
    // If true, the language preference will be stored in a cookie
    // This allows the language to persist across requests without showing it in the URL
    'use_cookies' => false,

    // Whether to detect language from header
    'detect_from_header' => true,

    // The name of the header to check for language
    'header_name' => 'Accept-Language',

    // Default locale if no language is detected
    'default_locale' => 'en',

    // Allowed locales
    'allowed_locales' => [
        'en',
        'pl',
        'de',
        'fr',
        'es',
        'en_GB',
        'en_US',
    ],

    // API prefixes
    'api_prefixes' => [
        'api',
    ],

    // Cookie settings
    'cookie_name' => 'language',
    'cookie_lifetime' => 43200, // 30 days

    // Language change route
    // This route will be registered automatically by the service provider
    'language_change_route' => 'change-language',
];
```

### Usage

Register the middleware in your `app/Http/Kernel.php` file:

```php
protected $routeMiddleware = [
    // Other middleware...
    'language' => \Dominservice\DataLocaleParser\Http\Middleware\LanguageMiddleware::class,
];
```

#### Cookie Encryption

By default, Laravel encrypts all cookies. If you're using cookies for language storage (`use_cookies` set to `true`), you need to exclude the language cookie from encryption. Otherwise, the middleware might not be able to read the cookie correctly.

Add the language cookie name to the `$except` array in your `app/Http/Middleware/EncryptCookies.php` file:

```php
namespace App\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;

class EncryptCookies extends Middleware
{
    /**
     * The names of the cookies that should not be encrypted.
     *
     * @var array<int, string>
     */
    protected $except = [
        'language', // Add the language cookie name here (use the value from your config)
    ];
}
```

If you've changed the cookie name in your configuration, make sure to use that name instead of 'language'.

#### Cookie Handling

This package sets cookies directly on the response object to ensure they are properly included in the response. The package automatically applies the `web` middleware group to its routes to ensure that cookies work correctly.

Apply the middleware to your routes:

```php
// Apply to specific routes
Route::get('/{any}', 'HomeController@index')->middleware('language')->where('any', '.*');

// Apply to route groups
Route::middleware(['language'])->group(function () {
    Route::get('/', 'HomeController@index');
    Route::get('/{any}', 'HomeController@index')->where('any', '.*');
});

// Apply to API routes
Route::prefix('api')->middleware(['language'])->group(function () {
    Route::get('/{lang}/users', 'Api\UserController@index');
});
```

### How It Works

1. **URL Detection**: The middleware checks if the first segment of the URL path is a valid language code. For API routes (URLs starting with a prefix defined in `api_prefixes`), it checks the second segment.

2. **Header Detection**: If URL detection is disabled or fails, the middleware checks the request header specified in the configuration.

3. **Default Locale**: If no language is detected from the URL or header, the middleware uses the default locale specified in the configuration.

4. **Cookie Storage**: If `use_cookies` is enabled, the language preference will be stored in a cookie. This allows the language to persist across requests without showing it in the URL. For non-API routes, the middleware will check for the language in the cookie before checking the URL.

### Changing the Language

The package automatically registers a route for changing the language. By default, this route is `/change-language/{language}`, but you can customize it in the configuration.

To create a language switcher in your application, you can use the following example:

```php
<ul class="language-switcher">
    @foreach(config('data_locale_parser.allowed_locales') as $locale)
        <li>
            <a href="{{ route('language.change', ['language' => $locale]) }}">
                {{ strtoupper($locale) }}
            </a>
        </li>
    @endforeach
</ul>
```

When a user clicks on a language link, the following happens:

1. The language is validated against the allowed locales and the language database.
2. If valid, the language is set as the application locale.
3. If `use_cookies` is enabled, the language preference is stored in a cookie.
4. The user is redirected back to the previous page using one of the following methods:
   - **Route Name-Based Redirection**: If the previous page has a named route with a locale prefix (e.g., `en.contact`), the controller will replace the locale in the route name and redirect to the new route with the same parameters. This allows for path translation (e.g., `/kontakt` to `/contact`) when your routes are properly localized.
   - **URL Segment Manipulation**: If route name-based redirection fails or the route doesn't have a name, the controller falls back to URL segment manipulation:
     - If `use_cookies` is enabled, the language prefix is removed from the URL.
     - If `use_cookies` is disabled, the language prefix is updated or added to the URL.

This allows you to create a seamless language switching experience for your users, with or without showing the language in the URL, and with support for path translation when using named routes.

### Setting Up Localized Routes

To take advantage of the route name-based redirection and path translation, you need to set up your routes with locale prefixes in their names. Here's an example:

```php
// routes/web.php

// English routes
Route::prefix('en')->name('en.')->middleware(['language'])->group(function () {
    Route::get('/', 'HomeController@index')->name('home');
    Route::get('/contact', 'ContactController@index')->name('contact');
    Route::get('/about', 'AboutController@index')->name('about');
});

// Polish routes
Route::prefix('pl')->name('pl.')->middleware(['language'])->group(function () {
    Route::get('/', 'HomeController@index')->name('home');
    Route::get('/kontakt', 'ContactController@index')->name('contact');
    Route::get('/o-nas', 'AboutController@index')->name('about');
});

// German routes
Route::prefix('de')->name('de.')->middleware(['language'])->group(function () {
    Route::get('/', 'HomeController@index')->name('home');
    Route::get('/kontakt', 'ContactController@index')->name('contact');
    Route::get('/uber-uns', 'AboutController@index')->name('about');
});
```

With this setup, when a user switches from Polish to English while on the `/pl/kontakt` page, they will be redirected to `/en/contact` instead of just changing the language prefix.

## Helper Functions

This package includes several helper functions to make working with localized routes and URLs easier. These functions are automatically loaded when you install the package.

### RTL Detection

```php
locale_is_rtl(?string $locale = null): bool
```

Checks if a locale is RTL (Right-to-Left). If no locale is provided, it uses the current application locale.

```php
if (locale_is_rtl('ar')) {
    // Arabic is an RTL language
}

if (locale_is_rtl()) {
    // The current locale is an RTL language
}
```

You can configure which locales are considered RTL in the `config/data_locale_parser.php` file:

```php
'locale_rtl' => [
    'ar',    // Arabic
    'fa',    // Persian (Farsi)
    'he',    // Hebrew
    'ur',    // Urdu
    'yi',    // Yiddish
    // ... other RTL languages
],
```

### Route Translation

```php
get_translated_route(string $locale, string $key): string
```

Gets a translated route key for a specific locale. This is useful for translating route names or segments.

```php
$translatedKey = get_translated_route('pl', 'contact');
// Returns 'kontakt' if a translation exists, otherwise 'pl.contact'
```

### Localized Routes

```php
route_current_locale(string $route, mixed $parameters = [], bool $absolute = true): string
```

Generates a URL to a named route for the current locale.

```php
$url = route_current_locale('contact');
// If the current locale is 'en', returns URL for 'en.contact'
```

```php
route_locale(string $locale, string $route, mixed $parameters = [], bool $absolute = true): string
```

Generates a URL to a named route for a specific locale.

```php
$url = route_locale('pl', 'contact');
// Returns URL for 'pl.contact'
```

### Route Checking

```php
is_route_current_locale(mixed ...$patterns): bool
```

Checks if the current route name matches any of the given patterns for the current locale.

```php
if (is_route_current_locale('contact', 'about')) {
    // Current route is either 'en.contact' or 'en.about' (assuming current locale is 'en')
}
```

### URL Localization

```php
get_localized_url(string $locale): string
```

Gets the URL for the current page in a different locale. This function tries to use route name-based redirection first, and falls back to URL segment manipulation if that fails.

```php
$plUrl = get_localized_url('pl');
// If current URL is '/en/contact', returns '/pl/kontakt' (if route names are properly set up)
```

## Examples

For more advanced usage examples, check out the [examples directory](examples/). It contains sample code demonstrating various features of the library, including:

- Enhanced language full data methods with caching, custom display locales, and advanced filtering
- Single language data retrieval
- Language handling middleware usage
- And more

## Credits

- [UN/LOCODE Country Subdivisions ISO 3166-2](https://unece.org/trade/uncefact/unlocode-country-subdivisions-iso-3166-2)
- [Monarobase/country-list](https://github.com/Monarobase/country-list)
- [umpirsky/language-list](https://github.com/umpirsky/language-list)
- [umpirsky/locale-list](https://github.com/umpirsky/locale-list)
- [ipregistry/iso3166](https://github.com/ipregistry/iso3166)

---

## Support
### Support this project (Ko‑fi)
If this package saves you time, consider buying me a coffee: https://ko-fi.com/dominservice — thank you!

---

## License

MIT © Dominservice