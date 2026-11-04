<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Backpack API access middleware
 * Only works with backpack guard authentication
 */
class RequireBackpackApiAccess
{
    /**
     * Handle backpack API access check
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only check backpack guard - never mix with web
        $auth = backpack_auth();
        if (!$auth->check()) {
            $defaultAuth = auth();
            Log::warning(
                'Backpack authentication failed',
                [
                    'path' => $request->path(),
                    'backpack_check' => $auth->check(),
                    'laravel_auth_check' => $defaultAuth->check(),
                ],
            );

            return $this->unauthorized($request);
        }

        $user = $auth->user();
        $permissions = $user->getAllPermissions();
        $permissionNames = $permissions->pluck('name');
        $permissionsWithGuard = $permissions->map(
            function ($p) {
                return $p->name . ' (' . $p->guard_name . ')';
            },
        );
        Log::debug(
            'Backpack user authenticated',
            [
                'user_id' => $user->id,
                'email' => $user->email,
                'path' => $request->path(),
                'has_admin_role' => $user->hasRole('admin'),
                'all_permissions' => $permissionNames->toArray(),
                'permissions_with_guard' => $permissionsWithGuard->toArray(),
            ],
        );

        // Check if user has backpack API access permission
        if (!$user->hasPermissionTo('backpack.api.access', 'backpack')) {
            Log::warning(
                'Backpack user lacks API access permission',
                [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'path' => $request->path(),
                    'checked_permission' => 'backpack.api.access',
                    'checked_guard' => 'backpack',
                ],
            );

            return $this->forbidden($request);
        }

        return $next($request);
    }

    /**
     * Return unauthorized response
     */
    private function unauthorized(Request $request): Response
    {
        if ($request->expectsJson() || $request->ajax()) {
            $responseFactory = response();

            return $responseFactory->json(
                [
                    'error' => __('backpack::base.unauthorized'),
                    'code' => 401,
                ],
                401,
            );
        }

        $redirector = redirect();

        return $redirector->guest(
            backpack_url('login'),
        );
    }

    /**
     * Return forbidden response
     */
    private function forbidden(Request $request): Response
    {
        if ($request->expectsJson() || $request->ajax()) {
            $responseFactory = response();

            return $responseFactory->json(
                [
                    'error' => __('backpack::crud.unauthorized_access'),
                    'code' => 403,
                ],
                403,
            );
        }

        // Redirect to dashboard URL (backpack_url already returns full path)
        $redirect = redirect(
            backpack_url('dashboard'),
        );

        return $redirect->with(
            'error',
            __('backpack::crud.unauthorized_access'),
        );
    }
}
