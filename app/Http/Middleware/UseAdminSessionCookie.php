<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Config\Repository;
use Illuminate\Http\Request;
use Illuminate\Session\SessionManager;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gives the admin its own session cookie, so logging out of the admin or the frontend
 * (both invalidate the whole session) does not log the other one out.
 *
 * Runs as global middleware, before the web group and Sanctum start the session.
 */
final class UseAdminSessionCookie
{
    public function __construct(
        private readonly Repository $config,
        private readonly SessionManager $sessions,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $name = $this->config->string('session.cookie');
        $prefix = $this->config->string('backpack.base.route_prefix');

        if (
            $request->is(
                $prefix,
                $prefix . '/*',
                'api/' . $prefix . '/*',
            )
        ) {
            $name .= '_admin';
        }

        // The driver outlives one request in tests and Octane, so the name is set every time.
        $session = $this->sessions->driver();
        $session->setName($name);

        return $next($request);
    }
}
