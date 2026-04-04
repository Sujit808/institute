<?php

namespace App\Http\Middleware;

use App\Models\LicenseConfig;
use App\Support\SchoolModuleRegistry;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleAccess
{
    private const STUDENT_ALLOWED_MODULES = [
        'exams',
        'exam-papers',
        'study-materials',
        'fees',
        'attendance',
        'calendar',
        'results',
    ];

    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = $request->user();
        $normalizedModule = SchoolModuleRegistry::normalizePermissionKey($module);

        if ($user && $user->isStudent()) {
            // Students can only pass module checks on student portal routes.
            if (! $request->is('student/*')) {
                abort(403, 'You do not have permission to access this area.');
            }

            if (! in_array($normalizedModule, self::STUDENT_ALLOWED_MODULES, true)) {
                $label = SchoolModuleRegistry::lookupPermissions()[$normalizedModule] ?? $normalizedModule;
                abort(403, 'You do not have permission to access the '.$label.' area.');
            }

            $license = LicenseConfig::current();
            if ($license && ! $license->moduleEnabled($normalizedModule)) {
                $label = SchoolModuleRegistry::lookupPermissions()[$normalizedModule] ?? $normalizedModule;
                abort(403, 'The '.$label.' module is disabled in master control.');
            }

            return $next($request);
        }

        if (! $user || ! $user->canAccessModule($module)) {
            $label = SchoolModuleRegistry::lookupPermissions()[$normalizedModule] ?? $module;
            abort(403, 'You do not have permission to access the '.$label.' area.');
        }

        return $next($request);
    }
}
