<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockExternalAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('security.access.block_external', false)) {
            return $next($request);
        }

        $ip = (string) $request->ip();

        if ($ip === '') {
            abort(403, 'Access denied.');
        }

        $allowLocalhost = (bool) config('security.access.allow_localhost', true);
        if ($allowLocalhost && in_array($ip, ['127.0.0.1', '::1'], true)) {
            return $next($request);
        }

        $allowedIps = collect((array) config('security.access.allowed_ips', []))
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->values();

        if ($allowedIps->contains($ip)) {
            return $next($request);
        }

        abort(403, 'External access is blocked.');
    }
}
