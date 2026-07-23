<?php

declare(strict_types=1);

namespace Dominservice\DataLocaleParser\Tests\Unit;

use Dominservice\DataLocaleParser\DataLocaleParserServiceProvider;
use Dominservice\DataLocaleParser\DataParser;
use Dominservice\DataLocaleParser\Facade\DataParserFacade;
use Dominservice\DataLocaleParser\Http\Middleware\LanguageMiddleware;
use Dominservice\DataLocaleParser\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

final class FrameworkContractTest extends TestCase
{
    public function test_service_provider_registers_configuration_route_and_parser(): void
    {
        self::assertTrue(config('data_locale_parser.detect_from_url'));
        self::assertSame('en', config('data_locale_parser.default_locale'));
        self::assertTrue(Route::has('language.change'));
        self::assertInstanceOf(DataParser::class, app(DataParser::class));
        self::assertSame(
            LanguageMiddleware::class,
            app('router')->getMiddleware()['language'] ?? null
        );
        self::assertSame(
            [DataParser::class],
            app()->getProvider(DataLocaleParserServiceProvider::class)?->provides()
        );
    }

    public function test_explicit_url_locale_wins_and_reaches_next_middleware(): void
    {
        config()->set('data_locale_parser.use_cookies', true);
        app()->setLocale('en');

        $request = Request::create('/pl/example', 'GET', [], ['language' => 'de']);
        $response = (new LanguageMiddleware())->handle(
            $request,
            fn () => new Response(app()->currentLocale(), 218)
        );

        self::assertSame('pl', app()->currentLocale());
        self::assertSame(218, $response->getStatusCode());
        self::assertSame('pl', $response->getContent());
    }

    public function test_route_helpers_keep_named_route_contract(): void
    {
        self::assertSame('http://localhost/contact', route_locale('en', 'contact'));
        self::assertSame('http://localhost/pl/kontakt', route_locale('pl', 'contact'));
        self::assertSame('/pl/kontakt', route_locale('pl', 'contact', [], false));
    }

    public function test_facade_resolves_the_registered_parser(): void
    {
        self::assertSame('Poland', DataParserFacade::getCountry('PL', 'en'));
        self::assertSame('złoty polski', DataParserFacade::getCurrency('PLN', 'pl'));
        self::assertSame('polski', DataParserFacade::getLanguage('pl', 'pl'));
    }
}
