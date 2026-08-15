<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReplaceLegacyBranding
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $contentType = strtolower((string) $response->headers->get('Content-Type'));

        if (! str_contains($contentType, 'text/html')) {
            return $response;
        }

        $content = $response->getContent();

        if (is_string($content) && str_contains($content, 'Geezap')) {
            $response->setContent(str_replace('Geezap', 'Best Way Jobs', $content));
        }

        return $response;
    }
}
