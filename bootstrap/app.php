<?php

use App\Exceptions\ExceptionHandling;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\RefreshBackpackSession;
use App\Http\Middleware\RefreshFrontendSession;
use App\Http\Middleware\RequireBackpackAccess;
use App\Http\Middleware\RequireBackpackApiAccess;
use App\Http\Middleware\RequireFrontendApiAccess;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->statefulApi();

        $middleware->alias([
            'api.require.frontend' => RequireFrontendApiAccess::class,
            'api.require.backpack' => RequireBackpackApiAccess::class,
            'refresh.frontend.session' => RefreshFrontendSession::class,
            'refresh.backpack.session' => RefreshBackpackSession::class,
            'set.locale' => SetLocale::class,
            'guest' => RedirectIfAuthenticated::class,
            'admin' => RequireBackpackAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        ExceptionHandling::registerCallbacks($exceptions);
    })->create();
