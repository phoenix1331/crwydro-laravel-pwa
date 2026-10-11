<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Versioned API contract between the PWA and the server. Every response says
 * which version the server speaks, so a cached client can tell it is behind.
 * A client older than the minimum is refused with 426 before it can write
 * in an outdated shape; its outbox simply waits until it updates.
 */
class ApiVersion
{
    public function handle(Request $request, Closure $next): Response
    {
        $client = $request->header('X-Crwydro-Client-Api');

        if ($client !== null && (int) $client < config('crwydro.min_client_api')) {
            return response()
                ->json(['message' => 'This version of Crwydro is out of date. Reload to update.'], 426)
                ->header('X-Crwydro-Api', (string) config('crwydro.api_version'));
        }

        $response = $next($request);
        $response->headers->set('X-Crwydro-Api', (string) config('crwydro.api_version'));

        return $response;
    }
}
