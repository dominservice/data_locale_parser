<?php

declare(strict_types=1);

namespace Dominservice\DataLocaleParser\Tests\Snapshots;

use Dominservice\DataLocaleParser\DataParser;
use Dominservice\DataLocaleParser\Http\Controllers\LanguageController;
use Dominservice\DataLocaleParser\Http\Middleware\LanguageMiddleware;
use Dominservice\DataLocaleParser\Http\Middleware\SetLocaleMiddleware;
use Dominservice\DataLocaleParser\Tests\Support\InteractsWithSnapshots;
use Dominservice\DataLocaleParser\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

final class FrameworkBehaviorSnapshotTest extends TestCase
{
    use InteractsWithSnapshots;

    public function test_helpers_and_routes_match_snapshot(): void
    {
        $originalServer = $_SERVER;

        try {
            $ssl = [];

            foreach ([
                'no_indicators' => [],
                'https_on' => ['HTTPS' => 'on'],
                'https_one' => ['HTTPS' => '1'],
                'port_443' => ['SERVER_PORT' => '443'],
                'forwarded_proto' => ['HTTP_X_FORWARDED_PROTO' => 'https'],
                'forwarded_ssl' => ['HTTP_X_FORWARDED_SSL' => 'on'],
                'http' => ['HTTPS' => 'off', 'SERVER_PORT' => '80'],
            ] as $scenario => $server) {
                $_SERVER = $server;
                $ssl[$scenario] = is_ssl();
            }
        } finally {
            $_SERVER = $originalServer;
        }

        app()->setLocale('pl');

        $snapshot = [
            'schema' => 1,
            'ssl' => $ssl,
            'rtl' => [
                'ar' => locale_is_rtl('ar'),
                'he' => locale_is_rtl('he'),
                'pl' => locale_is_rtl('pl'),
                'current_pl' => locale_is_rtl(),
            ],
            'translated_route' => [
                'default' => get_translated_route('en', 'routes.contact'),
                'missing_translation' => get_translated_route('pl', 'routes.contact'),
            ],
            'route_locale' => [
                'unprefixed' => route_locale('en', 'contact'),
                'localized' => route_locale('pl', 'contact'),
                'localized_relative' => route_locale('pl', 'contact', [], false),
                'fallback_to_base' => route_locale('fr', 'contact'),
                'missing' => route_locale('de', 'missing'),
                'current' => route_current_locale('contact'),
            ],
            'localized_url_request' => $this->getJson('/pl/artykul/test-slug')->json(),
            'provider' => [
                'language_change_route_exists' => Route::has('language.change'),
                'language_change_uri' => Route::getRoutes()->getByName('language.change')?->uri(),
                'language_middleware_alias' => app('router')->getMiddleware()['language'] ?? null,
                'parser_is_singleton' => app(DataParser::class) === app(DataParser::class),
            ],
        ];

        $this->assertMatchesJsonSnapshot('framework/helpers-and-routes.json', $snapshot);
    }

    public function test_language_middlewares_match_snapshot(): void
    {
        $snapshot = [
            'schema' => 1,
            'language_middleware' => [],
            'set_locale_middleware' => [],
        ];

        $snapshot['language_middleware']['url'] = $this->runLanguageMiddleware(
            Request::create('/pl/page', 'GET')
        );
        $snapshot['language_middleware']['api_url'] = $this->runLanguageMiddleware(
            Request::create('/api/de/resource', 'GET')
        );
        $snapshot['language_middleware']['dynamic_valid_language'] = $this->runLanguageMiddleware(
            Request::create('/ja/page', 'GET')
        );
        $snapshot['language_middleware']['unprefixed'] = $this->runLanguageMiddleware(
            Request::create('/contact', 'GET')
        );

        config()->set('data_locale_parser.detect_from_url', false);
        config()->set('data_locale_parser.use_cookies', true);
        $snapshot['language_middleware']['cookie'] = $this->runLanguageMiddleware(
            Request::create('/contact', 'GET', [], ['language' => 'de'])
        );
        $snapshot['language_middleware']['header'] = $this->runLanguageMiddleware(
            Request::create('/contact', 'GET', [], [], [], ['HTTP_ACCEPT_LANGUAGE' => 'fr'])
        );
        $snapshot['language_middleware']['complex_header'] = $this->runLanguageMiddleware(
            Request::create('/contact', 'GET', [], [], [], [
                'HTTP_ACCEPT_LANGUAGE' => 'pl-PL,pl;q=0.9,en;q=0.8',
            ])
        );
        $snapshot['language_middleware']['invalid'] = $this->runLanguageMiddleware(
            Request::create('/contact', 'GET', [], [], [], ['HTTP_ACCEPT_LANGUAGE' => '__invalid__'])
        );

        config()->set('data_locale_parser.use_cookies', false);
        config()->set('data_locale_parser.cookie_name', 'Language Preference');
        app()->setLocale('en');
        $request = Request::create('/contact', 'GET', [], ['language-preference' => 'pl']);
        $response = (new SetLocaleMiddleware())->handle(
            $request,
            fn () => new Response(app()->currentLocale(), 209)
        );
        $snapshot['set_locale_middleware']['cookie'] = [
            'locale' => app()->currentLocale(),
            'status' => $response->getStatusCode(),
            'content' => $response->getContent(),
        ];

        app()->setLocale('de');
        $response = (new SetLocaleMiddleware())->handle(
            Request::create('/contact'),
            fn () => new Response(app()->currentLocale(), 210)
        );
        $snapshot['set_locale_middleware']['without_cookie'] = [
            'locale' => app()->currentLocale(),
            'status' => $response->getStatusCode(),
            'content' => $response->getContent(),
        ];

        $this->assertMatchesJsonSnapshot('framework/middleware.json', $snapshot);
    }

    public function test_language_controller_matches_snapshot(): void
    {
        $controller = app(LanguageController::class);
        $snapshot = [
            'schema' => 1,
        ];

        config()->set('data_locale_parser.use_cookies', false);
        $snapshot['localized_named_route'] = $this->runController(
            $controller,
            'pl',
            'https://example.test/en/contact'
        );
        $snapshot['unprefixed_named_route'] = $this->runController(
            $controller,
            'en',
            'https://example.test/pl/kontakt'
        );
        $snapshot['path_fallback'] = $this->runController(
            $controller,
            'de',
            'https://example.test/en/not-a-route?from=test#section'
        );
        $snapshot['without_referer'] = $this->runController($controller, 'pl', null);
        $snapshot['invalid_language'] = $this->runController(
            $controller,
            '__invalid__',
            'https://example.test/en/contact'
        );

        config()->set('data_locale_parser.use_cookies', true);
        $snapshot['cookie_mode'] = $this->runController(
            $controller,
            'de',
            'https://example.test/en/not-a-route?from=test'
        );

        $this->assertMatchesJsonSnapshot('framework/controller.json', $snapshot);
    }

    /**
     * @return array<string, mixed>
     */
    private function runLanguageMiddleware(Request $request): array
    {
        Cookie::flushQueuedCookies();
        app()->setLocale('__before__');

        $response = (new LanguageMiddleware())->handle(
            $request,
            fn () => new Response(app()->currentLocale(), 207)
        );

        $queuedCookies = [];

        foreach (Cookie::getQueuedCookies() as $cookie) {
            $queuedCookies[] = [
                'name' => $cookie->getName(),
                'value' => $cookie->getValue(),
            ];
        }

        return [
            'locale' => app()->currentLocale(),
            'status' => $response->getStatusCode(),
            'content' => $response->getContent(),
            'queued_cookies' => $queuedCookies,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function runController(
        LanguageController $controller,
        string $language,
        ?string $referer
    ): array {
        Cookie::flushQueuedCookies();
        app()->setLocale('__before__');

        $request = Request::create('/change-language/'.$language, 'GET');

        if ($referer !== null) {
            $request->headers->set('referer', $referer);
        }

        $response = $controller->changeLanguage($request, $language);
        $cookies = [];

        foreach (Cookie::getQueuedCookies() as $cookie) {
            $cookies[] = [
                'name' => $cookie->getName(),
                'value' => $cookie->getValue(),
            ];
        }

        return [
            'locale' => app()->currentLocale(),
            'status' => $response->getStatusCode(),
            'target' => $response->getTargetUrl(),
            'queued_cookies' => $cookies,
        ];
    }
}
