<?php

namespace App\Http\Middleware;

use App\Jobs\UpdateUserCountryFromCloudflare;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CaptureCloudflareCountry
{
    public function handle(Request $request, Closure $next): Response
    {
        $routeName = $request->route()?->getName();
        $cfCountry = $request->header('CF-IPCountry');

        if (auth()->check() && $routeName === 'home' && filled($cfCountry)) {
            UpdateUserCountryFromCloudflare::dispatch(
                userId: (string) auth()->id(),
                cfCountry: $cfCountry
            );
        }

        return $next($request);
    }
}
