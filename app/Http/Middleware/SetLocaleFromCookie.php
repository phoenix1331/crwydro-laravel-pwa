<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Server-rendered pages follow the language the walker chose in the app,
 * which the client mirrors into the `crwydro_locale` cookie.
 */
class SetLocaleFromCookie
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->cookie('crwydro_locale');

        if (in_array($locale, ['en', 'cy'], true)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
