<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\RefreshFrontendSession;
use App\Http\Middleware\RefreshBackpackSession;
use App\Http\Middleware\RequireFrontendApiAccess;
use App\Http\Middleware\RequireBackpackApiAccess;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(prepend: [
            SetLocale::class,
            RefreshFrontendSession::class // Refreshes the frontend session
        ]);

        // Ujistěte se, že VerifyCsrfToken middleware je v seznamu pro web middleware
        $middleware->web(append: [
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        ]);

        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        ]);
        
        // Registrace vlastního middleware jako alias
        $middleware->alias([
            'api.require.frontend' => RequireFrontendApiAccess::class,
            'api.require.backpack' => RequireBackpackApiAccess::class,
            'refresh.frontend.session' => RefreshFrontendSession::class,
            'refresh.backpack.session' => RefreshBackpackSession::class,
            'set.locale' => SetLocale::class,
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
    })->create();
