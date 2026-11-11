<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\User\Enums\Permission;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to check if user has admin access via roles with 'backpack' guard
 */
final class RequireBackpackAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated with backpack guard
        $auth = backpack_auth();
        if (!$auth->check()) {
            return $this->respondToUnauthorizedRequest($request, false);
        }

        $user = backpack_user();

        // Check if user has any role with 'backpack' guard
        $roles = $user->roles();
        $roles->where('guard_name', 'backpack');
        $hasBackpackRole = $roles->exists();

        if (!$hasBackpackRole) {
            Log::warning(
                'User without backpack roles attempted to access admin area',
                [
                    'user_id' => $user->id,
                    'path' => $request->path(),
                ],
            );

            return $this->respondToUnauthorizedRequest($request, true);
        }

        // If user has backpack role, they should have access
        // But let's also verify the permission as additional security layer
        if (!$user->checkPermissionTo(Permission::BACKPACK_ACCESS, 'backpack')) {
            Log::warning(
                'User with backpack role but without backpack.access permission attempted to access admin area',
                [
                    'user_id' => $user->id,
                    'path' => $request->path(),
                ],
            );

            return $this->respondToUnauthorizedRequest($request, true);
        }

        return $next($request);
    }

    /**
     * Handle unauthorized access
     */
    private function respondToUnauthorizedRequest(Request $request, bool $isAuthenticated = false): Response
    {
        if ($request->ajax() || $request->wantsJson()) {
            $statusCode = $isAuthenticated ? 403 : 401;
            $message = $isAuthenticated ? __('users.auth.no_admin_access') : __('users.auth.unauthenticated');

            $responseFactory = response();

            return $responseFactory->json(
                [
                    'error' => $message,
                    'code' => $statusCode,
                ],
                $statusCode,
            );
        }

        if ($isAuthenticated) {
            // User is logged in but doesn't have proper roles
            // Log them out and redirect to login with error message
            $auth = backpack_auth();
            $auth->logout();

            $redirector = redirect();
            $redirect = $redirector->route('backpack.auth.login');

            return $redirect->withErrors(
                [
                    'email' => __('admin.auth.no_backpack_role'),
                ],
            );
        }

        // User is not logged in - redirect to login
        $redirector = redirect();

        return $redirector->guest(
            backpack_url('login'),
        );
    }
}
