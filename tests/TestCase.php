<?php

declare(strict_types=1);

namespace Dominservice\DataLocaleParser\Tests;

use Dominservice\DataLocaleParser\DataLocaleParserServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use RuntimeException;

abstract class TestCase extends Orchestra
{
    private static ?string $testVendorPath = null;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::prepareTestVendorPath();
    }

    public static function tearDownAfterClass(): void
    {
        self::removeTestVendorPath();

        parent::tearDownAfterClass();
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            DataLocaleParserServiceProvider::class,
        ];
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('t', 32)));
        $app['config']->set('app.url', 'https://example.test');
        $app['config']->set('app.locale', 'en');
        $app['config']->set('data_locale_parser.detect_from_url', true);
        $app['config']->set('data_locale_parser.detect_from_header', true);
        $app['config']->set('data_locale_parser.use_cookies', false);
        $app['config']->set('data_locale_parser.default_locale', 'en');
        $app['config']->set('data_locale_parser.unprefixed_locale', 'en');
        $app['config']->set('data_locale_parser.allowed_locales', [
            'en',
            'pl',
            'de',
            'fr',
            'es',
            'ar',
            'en_GB',
            'en_US',
            'pl_PL',
        ]);
        $app['config']->set('data_locale_parser.api_prefixes', ['api']);
        $app['config']->set('data_locale_parser.cookie_name', 'language');
        $app['config']->set('data_locale_parser.cookie_lifetime', 43200);
    }

    /**
     * @param \Illuminate\Routing\Router $router
     */
    protected function defineRoutes($router): void
    {
        $router->get('/', fn () => 'home')->name('home');
        $router->get('/contact', fn () => 'contact')->name('contact');
        $router->get('/en/contact', fn () => 'english contact')->name('en.contact');
        $router->get('/pl/kontakt', fn () => 'polski kontakt')->name('pl.contact');
        $router->get('/de/kontakt', fn () => 'deutscher kontakt')->name('de.contact');

        $router->get('/article/{slug}', fn (string $slug) => $slug)->name('article');
        $router->get('/en/article/{slug}', fn (string $slug) => $slug)->name('en.article');
        $router->get('/pl/artykul/{slug}', function (string $slug) {
            app()->setLocale('pl');

            return response()->json([
                'slug' => $slug,
                'localized_en' => get_localized_url('en'),
                'localized_de' => get_localized_url('de'),
                'current_route_matches' => is_route_current_locale('article|contact'),
                'current_locale_url' => route_current_locale('article', ['slug' => $slug]),
            ]);
        })->name('pl.article');
        $router->get('/de/artikel/{slug}', fn (string $slug) => $slug)->name('de.article');
    }

    private static function prepareTestVendorPath(): void
    {
        $suffix = substr(sha1(dirname(__DIR__)), 0, 12).'-'.getmypid();
        $vendorPath = sys_get_temp_dir().'/data-locale-parser-tests-'.$suffix;
        $ownerPath = $vendorPath.'/dominservice';
        $packagePath = $ownerPath.'/data_locale_parser';

        if (!is_dir($ownerPath) && !mkdir($ownerPath, 0777, true) && !is_dir($ownerPath)) {
            throw new RuntimeException(sprintf('Unable to create test vendor directory "%s".', $ownerPath));
        }

        if (!is_link($packagePath) && !file_exists($packagePath)) {
            $target = realpath(dirname(__DIR__));

            if ($target === false || !symlink($target, $packagePath)) {
                throw new RuntimeException(sprintf('Unable to link package into test vendor directory "%s".', $packagePath));
            }
        }

        self::$testVendorPath = $vendorPath;
        putenv('DATA_LOCALE_PARSER_TEST_VENDOR_PATH='.$vendorPath);
    }

    private static function removeTestVendorPath(): void
    {
        if (self::$testVendorPath === null) {
            return;
        }

        $packagePath = self::$testVendorPath.'/dominservice/data_locale_parser';
        $ownerPath = self::$testVendorPath.'/dominservice';

        if (is_link($packagePath)) {
            unlink($packagePath);
        }

        if (is_dir($ownerPath)) {
            rmdir($ownerPath);
        }

        if (is_dir(self::$testVendorPath)) {
            rmdir(self::$testVendorPath);
        }

        putenv('DATA_LOCALE_PARSER_TEST_VENDOR_PATH');
        self::$testVendorPath = null;
    }
}
