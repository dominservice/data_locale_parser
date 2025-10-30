<?php

namespace Dominservice\DataLocaleParser\Http\Middleware;

use Closure;
use Dominservice\DataLocaleParser\Fasade\DataParserFacade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

class SetLocaleMiddleware
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
        $cookieLocale = Str::slug(Config::get('data_locale_parser.cookie_name', 'language'));

        if ($request->hasCookie($cookieLocale)) {
            app()->setLocale($request->cookie($cookieLocale));
        }

        return $next($request);
    }
}
