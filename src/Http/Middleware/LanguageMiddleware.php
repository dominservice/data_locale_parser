<?php

namespace Dominservice\DataLocaleParser\Http\Middleware;

use Closure;
use Dominservice\DataLocaleParser\Fasade\DataParserFacade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;

class LanguageMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Get configuration
        $config = $this->getConfig();

        // Check if the URL starts with an API prefix
        $path = $request->path();
        $segments = explode('/', $path);
        $isApiRoute = !empty($segments[0]) && in_array($segments[0], $config['api_prefixes']);

        // For API routes, always use URL-based detection
        if ($isApiRoute && $config['detect_from_url']) {
            $language = $this->getLanguageFromUrl($request, $config);
            if ($language) {
                App::setLocale($language);

                // Store in cookie if configured to use cookies
                if ($config['use_cookies']) {
                    Cookie::queue($config['cookie_name'], $language, $config['cookie_lifetime']);
                }

                return $next($request);
            }
        } 
        // For non-API routes, use cookie or URL based on configuration
        else {
            // Check cookie first if enabled
            if ($config['use_cookies']) {
                $language = $this->getLanguageFromCookie($config);
                if ($language) {
                    App::setLocale($language);
                    return $next($request);
                }
            }

            // Then check URL if enabled
            if ($config['detect_from_url']) {
                $language = $this->getLanguageFromUrl($request, $config);
                if ($language) {
                    App::setLocale($language);

                    // Store in cookie if configured to use cookies
                    if ($config['use_cookies']) {
                        Cookie::queue($config['cookie_name'], $language, $config['cookie_lifetime']);
                    }

                    return $next($request);
                }
            }
        }

        // Get language from header if enabled
        if ($config['detect_from_header']) {
            $language = $this->getLanguageFromHeader($request, $config);
            if ($language) {
                App::setLocale($language);

                // Store in cookie if configured to use cookies
                if ($config['use_cookies']) {
                    Cookie::queue($config['cookie_name'], $language, $config['cookie_lifetime']);
                }

                return $next($request);
            }
        }

        // Use default language if no language is detected
        App::setLocale($config['default_locale']);

        return $next($request);
    }

    /**
     * Get language from URL
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $config
     * @return string|null
     */
    protected function getLanguageFromUrl(Request $request, array $config)
    {
        $path = $request->path();
        $segments = explode('/', $path);

        // Check if the URL starts with an API prefix
        if (!empty($segments[0]) && in_array($segments[0], $config['api_prefixes'])) {
            // For API routes, check the second segment
            if (!empty($segments[1])) {
                $potentialLanguage = $segments[1];

                // Check if the language is in the allowed locales
                if (in_array($potentialLanguage, $config['allowed_locales'])) {
                    return $potentialLanguage;
                }

                // Check if it's a valid language code
                try {
                    DataParserFacade::getLanguage($potentialLanguage);
                    return $potentialLanguage;
                } catch (\Exception $e) {
                    // Not a valid language code
                }
            }
        } else {
            // For regular routes, check the first segment
            if (!empty($segments[0])) {
                $potentialLanguage = $segments[0];

                // Check if the language is in the allowed locales
                if (in_array($potentialLanguage, $config['allowed_locales'])) {
                    return $potentialLanguage;
                }

                // Check if it's a valid language code
                try {
                    DataParserFacade::getLanguage($potentialLanguage);
                    return $potentialLanguage;
                } catch (\Exception $e) {
                    // Not a valid language code
                }
            }
        }

        return null;
    }

    /**
     * Get language from header
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $config
     * @return string|null
     */
    protected function getLanguageFromHeader(Request $request, array $config)
    {
        $header = $request->header($config['header_name']);

        if ($header) {
            // Check if the language is in the allowed locales
            if (in_array($header, $config['allowed_locales'])) {
                return $header;
            }

            // Check if it's a valid language code
            try {
                DataParserFacade::getLanguage($header);
                return $header;
            } catch (\Exception $e) {
                // Not a valid language code
            }
        }

        return null;
    }

    /**
     * Get language from cookie
     *
     * @param  array  $config
     * @return string|null
     */
    protected function getLanguageFromCookie(array $config)
    {
        $language = Cookie::get($config['cookie_name']);

        if ($language) {
            // Check if the language is in the allowed locales
            if (in_array($language, $config['allowed_locales'])) {
                return $language;
            }

            // Check if it's a valid language code
            try {
                DataParserFacade::getLanguage($language);
                return $language;
            } catch (\Exception $e) {
                // Not a valid language code
            }
        }

        return null;
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
