<?php

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Dominservice\DataLocaleParser\Fasade\DataParserFacade;

if (! function_exists('is_ssl')) {
    /**
     * Check if the current connection is using SSL
     *
     * @return bool
     */
    function is_ssl(): bool
    {
        if (isset($_SERVER['HTTPS'])) {
            if (strtolower($_SERVER['HTTPS']) == 'on') {
                return true;
            } elseif ($_SERVER['HTTPS'] == '1') {
                return true;
            }
        } elseif (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443') {
            return true;
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https') {
            return true;
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) == 'on') {
            return true;
        }

        return false;
    }
}

if (! function_exists('locale_is_rtl')) {
    /**
     * Check if a locale is RTL (Right-to-Left)
     *
     * @param string|null $locale The locale to check, defaults to current locale
     * @return bool
     */
    function locale_is_rtl(?string $locale = null): bool
    {
        $rtlLocales = Config::get('data_locale_parser.locale_rtl', ['ar', 'fa', 'he', 'ur']);
        return in_array($locale ?? App::currentLocale(), $rtlLocales);
    }
}

if (! function_exists('get_translated_route')) {
    /**
     * Get a translated route key
     *
     * @param string $locale The locale to translate to
     * @param string $key The route key to translate
     * @return string The translated route key
     */
    function get_translated_route(string $locale, string $key): string
    {
        $defaultLocale = Config::get('data_locale_parser.default_locale', 'en');

        // If the locale is the default locale, or if the translation exists and is different from the default
        return $locale === $defaultLocale
            || ($locale !== $defaultLocale
                && __($key, [], $locale) !== __($key, [], $defaultLocale))
                ? __($key, [], $locale)
                : $locale.'.'.$key;
    }
}

if (! function_exists('route_current_locale')) {
    /**
     * Generate a URL to a named route for the current locale
     *
     * @param string $route The route name without locale prefix
     * @param mixed $parameters Route parameters
     * @param bool $absolute Whether to generate an absolute URL
     * @return string The generated URL
     */
    function route_current_locale(string $route, mixed $parameters = [], bool $absolute = true): string
    {
        return route_locale(App::currentLocale(), $route, $parameters, $absolute);
    }
}

if (! function_exists('route_locale')) {
    /**
     * Generate a URL to a named route for a specific locale
     *
     * @param string $locale The locale
     * @param string $route The route name without locale prefix
     * @param mixed $parameters Route parameters
     * @param bool $absolute Whether to generate an absolute URL
     * @return string The generated URL
     */
    function route_locale(string $locale, string $route, mixed $parameters = [], bool $absolute = true): string
    {
        // Check if the route exists with the locale prefix
        $routeName = $locale.'.'.$route;
        if (!Route::has($routeName)) {
            // If the route doesn't exist with the locale prefix, try without it
            if (Route::has($route)) {
                return route($route, $parameters, $absolute);
            }

            // If neither exists, log a warning and return a fallback
            \Log::warning("Route not found: {$routeName} or {$route}");
            return url($locale.'/'.$route);
        }

        return route($routeName, $parameters, $absolute);
    }
}

if (! function_exists('is_route_current_locale')) {
    /**
     * Check if the current route name matches any of the given patterns for the current locale
     *
     * @param mixed ...$patterns One or more patterns to match against
     * @return bool True if the current route name matches any pattern for the current locale
     */
    function is_route_current_locale(mixed ...$patterns): bool
    {
        $locale = App::currentLocale();

        // Flatten arguments to a single array
        $patterns = collect($patterns)
            ->flatten()
            ->map(function($pattern) {
                return preg_split('/\s*[|,]\s*/', trim($pattern));
            })
            ->flatten()
            ->map(function($pattern) use ($locale) {
                return "{$locale}.{$pattern}";
            })
            ->all();

        return request()->routeIs(...$patterns);
    }
}

if (! function_exists('get_localized_url')) {
    /**
     * Get the URL for the current page in a different locale
     *
     * @param string $locale The target locale
     * @return string The URL for the current page in the target locale
     */
    function get_localized_url(string $locale): string
    {
        // Get the current route
        $route = Route::current();
        if (!$route) {
            return url($locale);
        }

        // Get the current route name and parameters
        $routeName = $route->getName();
        $routeParameters = $route->parameters();

        // If the route has a name
        if ($routeName) {
            // Extract the current locale from the route name
            $routeParts = explode('.', $routeName);
            $currentLocale = $routeParts[0];

            // Check if the first part of the route name is a valid locale
            $isLocaleInName = in_array($currentLocale, Config::get('data_locale_parser.allowed_locales', ['en']));

            if (!$isLocaleInName) {
                try {
                    DataParserFacade::getLanguage($currentLocale);
                    $isLocaleInName = true;
                } catch (\Exception $e) {
                    // Not a valid language code
                }
            }

            // If the first part is a locale, replace it with the new locale
            if ($isLocaleInName) {
                $newRouteName = preg_replace('/^' . $currentLocale . '\./', $locale . '.', $routeName);

                // Check if the new route exists
                if (Route::has($newRouteName)) {
                    return route($newRouteName, $routeParameters);
                }
            }
        }

        // Fallback: manipulate the URL directly
        $url = url()->current();
        $parsedUrl = parse_url($url);
        $path = $parsedUrl['path'] ?? '';

        // Split the path into segments
        $segments = explode('/', ltrim($path, '/'));

        // Check if the first segment is a language code
        if (!empty($segments[0])) {
            $firstSegment = $segments[0];

            // Check if it's a valid language code
            $isLanguageSegment = in_array($firstSegment, Config::get('data_locale_parser.allowed_locales', ['en']));

            if (!$isLanguageSegment) {
                try {
                    DataParserFacade::getLanguage($firstSegment);
                    $isLanguageSegment = true;
                } catch (\Exception $e) {
                    // Not a valid language code
                }
            }

            // If it's a language segment, replace it
            if ($isLanguageSegment) {
                $segments[0] = $locale;
                $newPath = '/' . implode('/', $segments);

                // Reconstruct the URL
                $scheme = isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] . '://' : '';
                $host = $parsedUrl['host'] ?? '';
                $port = isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '';
                $query = isset($parsedUrl['query']) ? '?' . $parsedUrl['query'] : '';
                $fragment = isset($parsedUrl['fragment']) ? '#' . $parsedUrl['fragment'] : '';

                return $scheme . $host . $port . $newPath . $query . $fragment;
            }
        }

        // If no language segment found, add the locale to the beginning
        return url($locale . $path);
    }
}
