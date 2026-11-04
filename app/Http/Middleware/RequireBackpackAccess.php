<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to check if user has admin access via roles with 'backpack' guard
 */
class RequireBackpackAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated with backpack guard
        if (!backpack_auth()->check()) {
            return $this->respondToUnauthorizedRequest($request, false);
        }

        $user = backpack_user();

        // Check if user has any role with 'backpack' guard
        $hasBackpackRole = $user->roles()
            ->where('guard_name', 'backpack')
            ->exists();

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
        if (!$user->hasPermissionTo('backpack.access', 'backpack')) {
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
            $message = $isAuthenticated
                ? __('users.auth.no_admin_access')
                : __('users.auth.unauthenticated');

            return response()->json([
                'error' => $message,
                'code' => $statusCode,
            ], $statusCode);
        }

        if ($isAuthenticated) {
            // User is logged in but doesn't have proper roles
            // Log them out and redirect to login with error message
            backpack_auth()->logout();

            return redirect()->route('backpack.auth.login')
                ->withErrors(
                    [
                        'email' => trans('admin.auth.no_backpack_role'),
                    ],
                );
        }

        // User is not logged in - redirect to login
        return redirect()->guest(
            backpack_url('login'),
        );
    }
}
