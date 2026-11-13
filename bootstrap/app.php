<?php

declare(strict_types=1);

use App\Exceptions\ExceptionHandling;
use App\Http\Middleware\EnsureUseCaseAllowed;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\RefreshBackpackSession;
use App\Http\Middleware\RefreshFrontendSession;
use App\Http\Middleware\RequireBackpackAccess;
use App\Http\Middleware\RequireBackpackApiAccess;
use App\Http\Middleware\RequireFrontendApiAccess;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\UseAdminSessionCookie;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$builder = Application::configure(
    basePath: dirname(__DIR__),
);

$builder->withRouting(
    web: __DIR__ . '/../routes/web.php',
    api: __DIR__ . '/../routes/api.php',
    commands: __DIR__ . '/../routes/console.php',
    health: '/up',
);

$builder->withMiddleware(
    function (Middleware $middleware): void {
        $middleware->statefulApi();
        $middleware->prepend(UseAdminSessionCookie::class);

        $middleware->alias(
            [
                'api.require.frontend' => RequireFrontendApiAccess::class,
                'api.require.backpack' => RequireBackpackApiAccess::class,
                'refresh.frontend.session' => RefreshFrontendSession::class,
                'refresh.backpack.session' => RefreshBackpackSession::class,
                'set.locale' => SetLocale::class,
                'guest' => RedirectIfAuthenticated::class,
                'admin' => RequireBackpackAccess::class,
                'allowed' => EnsureUseCaseAllowed::class,
            ],
        );
    },
);

$builder->withExceptions(
    function (Exceptions $exceptions): void {
        ExceptionHandling::registerCallbacks($exceptions);
    },
);

return $builder->create();
