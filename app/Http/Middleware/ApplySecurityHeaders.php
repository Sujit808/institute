<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplySecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! config('security.headers.enabled', true)) {
            return $response;
        }

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

        if ((bool) config('security.headers.hsts.enabled', true) && $request->isSecure()) {
            $maxAge = (int) config('security.headers.hsts.max_age', 31536000);
            $value = 'max-age='.$maxAge;

            if ((bool) config('security.headers.hsts.include_subdomains', true)) {
                $value .= '; includeSubDomains';
            }

            if ((bool) config('security.headers.hsts.preload', false)) {
                $value .= '; preload';
            }

            $response->headers->set('Strict-Transport-Security', $value);
        }

        $customCsp = trim((string) config('security.headers.content_security_policy', ''));
        if ($customCsp !== '') {
            $response->headers->set('Content-Security-Policy', $customCsp);
        }

        return $response;
    }
}
