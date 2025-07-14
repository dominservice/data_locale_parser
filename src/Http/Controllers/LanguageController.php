<?php

namespace Dominservice\DataLocaleParser\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request as RequestFacade;
use Dominservice\DataLocaleParser\Fasade\DataParserFacade;

class LanguageController
{
    /**
     * Change the application language
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $language
     * @return \Illuminate\Http\RedirectResponse
     */
    public function changeLanguage(Request $request, $language)
    {
        // Get configuration
        $config = $this->getConfig();

        // Validate language
        $isValidLanguage = false;

        // Check if the language is in the allowed locales
        if (in_array($language, $config['allowed_locales'])) {
            $isValidLanguage = true;
        } else {
            // Check if it's a valid language code
            try {
                DataParserFacade::getLanguage($language);
                $isValidLanguage = true;
            } catch (\Exception $e) {
                // Not a valid language code
            }
        }

        if (!$isValidLanguage) {
            return Redirect::back();
        }

        // Set the application locale
        App::setLocale($language);

        // Get the URL to redirect to
        $redirectUrl = $this->getRedirectUrl($request, $language, $config);

        // Create redirect response
        $redirect = Redirect::to($redirectUrl);

        // Store in cookie if configured to use cookies
        if ($config['use_cookies']) {
            Cookie::queue($config['cookie_name'], $language, $config['cookie_lifetime']);
        }

        return $redirect;
    }

    /**
     * Get the URL to redirect to after changing the language
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $language
     * @param  array  $config
     * @return string
     */
    protected function getRedirectUrl(Request $request, $language, array $config)
    {
        // Get the referer URL
        $referer = $request->headers->get('referer');

        // If no referer, redirect to home
        if (!$referer) {
            return '/';
        }

        // Parse the URL
        $parsedUrl = parse_url($referer);
        $path = $parsedUrl['path'] ?? '';

        // If the path is empty, redirect to home
        if (empty($path) || $path === '/') {
            return '/';
        }

        // Try to use route name-based redirection first
        try {
            // Get the route from the path
            $route = app('router')->getRoutes()->match(RequestFacade::create($path));

            // If we have a route with a name
            if ($route && $route->getName()) {
                $currentRouteName = $route->getName();
                $currentRouteParameters = $route->parameters();

                // Extract the current locale from the route name
                $routeParts = explode('.', $currentRouteName);
                $currentLocale = $routeParts[0];

                // Check if the first part of the route name is a valid locale
                $isLocaleInName = in_array($currentLocale, $config['allowed_locales']);

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
                    $newRouteName = preg_replace('/^' . $currentLocale . '\./', $language . '.', $currentRouteName);

                    // Redirect to the new route with the same parameters
                    return route($newRouteName, $currentRouteParameters);
                }
            }
        } catch (\Exception $e) {
            // If there's an error with route matching, fall back to URL segment manipulation
        }

        // Fall back to URL segment manipulation
        // Split the path into segments
        $segments = explode('/', ltrim($path, '/'));

        // Check if the first segment is a language code
        if (!empty($segments[0])) {
            $firstSegment = $segments[0];

            // Check if it's in the allowed locales or a valid language code
            $isLanguageSegment = in_array($firstSegment, $config['allowed_locales']);

            if (!$isLanguageSegment) {
                try {
                    DataParserFacade::getLanguage($firstSegment);
                    $isLanguageSegment = true;
                } catch (\Exception $e) {
                    // Not a valid language code
                }
            }

            // If it's a language segment and we're using cookies, remove it
            if ($isLanguageSegment && $config['use_cookies']) {
                array_shift($segments);
                $newPath = '/' . implode('/', $segments);

                // Reconstruct the URL
                $newUrl = $this->reconstructUrl($parsedUrl, $newPath);

                return $newUrl;
            }

            // If it's a language segment and we're not using cookies, replace it
            if ($isLanguageSegment && !$config['use_cookies']) {
                $segments[0] = $language;
                $newPath = '/' . implode('/', $segments);

                // Reconstruct the URL
                $newUrl = $this->reconstructUrl($parsedUrl, $newPath);

                return $newUrl;
            }
        }

        // If we're not using cookies, add the language to the path
        if (!$config['use_cookies']) {
            $newPath = '/' . $language . $path;

            // Reconstruct the URL
            $newUrl = $this->reconstructUrl($parsedUrl, $newPath);

            return $newUrl;
        }

        // Otherwise, just return the original path
        return $referer;
    }

    /**
     * Reconstruct a URL with a new path
     *
     * @param  array  $parsedUrl
     * @param  string  $newPath
     * @return string
     */
    protected function reconstructUrl(array $parsedUrl, string $newPath)
    {
        $scheme = isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] . '://' : '';
        $host = $parsedUrl['host'] ?? '';
        $port = isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '';
        $query = isset($parsedUrl['query']) ? '?' . $parsedUrl['query'] : '';
        $fragment = isset($parsedUrl['fragment']) ? '#' . $parsedUrl['fragment'] : '';

        return $scheme . $host . $port . $newPath . $query . $fragment;
    }

    /**
     * Get configuration
     *
     * @return array
     */
    protected function getConfig()
    {
        return [
            'detect_from_url' => Config::get('data_locale_parser.detect_from_url', true),
            'use_cookies' => Config::get('data_locale_parser.use_cookies', false),
            'detect_from_header' => Config::get('data_locale_parser.detect_from_header', true),
            'header_name' => Config::get('data_locale_parser.header_name', 'Accept-Language'),
            'default_locale' => Config::get('data_locale_parser.default_locale', 'en'),
            'allowed_locales' => Config::get('data_locale_parser.allowed_locales', ['en', 'pl', 'de', 'fr', 'es']),
            'api_prefixes' => Config::get('data_locale_parser.api_prefixes', ['api']),
            'cookie_name' => Config::get('data_locale_parser.cookie_name', 'language'),
            'cookie_lifetime' => Config::get('data_locale_parser.cookie_lifetime', 43200), // 30 days
            'language_change_route' => Config::get('data_locale_parser.language_change_route', 'change-language'),
        ];
    }
}
