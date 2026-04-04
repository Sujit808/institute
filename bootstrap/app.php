<?php

use App\Http\Middleware\ApplySecurityHeaders;
use App\Http\Middleware\BlockExternalAccess;
use App\Http\Middleware\EnsureModuleAccess;
use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\EnsureStudentRole;
use App\Http\Middleware\EnsureSuperAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append([
            BlockExternalAccess::class,
            ApplySecurityHeaders::class,
        ]);

        $middleware->alias([
            'ensure-password-changed' => EnsurePasswordChanged::class,
            'module' => EnsureModuleAccess::class,
            'student' => EnsureStudentRole::class,
            'super-admin' => EnsureSuperAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
