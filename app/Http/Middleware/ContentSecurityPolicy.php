<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ContentSecurityPolicy
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // In development with Vite, allow the Vite dev server
        if (app()->environment('local') && config('app.vite_dev_server_url')) {
            $viteUrl = config('app.vite_dev_server_url');
            $viteHost = parse_url($viteUrl, PHP_URL_HOST);
            $vitePort = parse_url($viteUrl, PHP_URL_PORT);
            $viteOrigin = $viteHost . ($vitePort ? ':' . $vitePort : '');

            $csp = implode('; ', [
                "default-src 'self'",
                "script-src 'self' 'unsafe-inline' 'unsafe-eval' {$viteOrigin}",
                "style-src 'self' 'unsafe-inline' {$viteOrigin} https://fonts.bunny.net",
                "img-src 'self' data: https:",
                "font-src 'self' data: https://fonts.bunny.net",
                "connect-src 'self' {$viteOrigin} ws://{$viteOrigin}",
                "frame-ancestors 'self'",
            ]);
        } else {
            // Production CSP (stricter)
            $csp = implode('; ', [
                "default-src 'self'",
                "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
                "style-src 'self' 'unsafe-inline' https://fonts.bunny.net",
                "img-src 'self' data: https:",
                "font-src 'self' data: https://fonts.bunny.net",
                "connect-src 'self'",
                "frame-ancestors 'self'",
            ]);
        }

        $response->headers->set('Content-Security-Policy', $csp);
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        return $response;
    }
}
