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
        if (!backpack_auth()->check()) {
            Log::warning('Backpack authentication failed', [
                'path' => $request->path(),
                'backpack_check' => backpack_auth()->check(),
                'laravel_auth_check' => auth()->check(),
                'laravel_auth_guard' => auth()->getDefaultDriver(),
            ]);
            return $this->unauthorized();
        }

        $user = backpack_auth()->user();
        Log::warning('Backpack user authenticated', [
            'user_id' => $user->id,
            'email' => $user->email,
            'path' => $request->path(),
            'has_admin_role' => $user->hasRole('admin'),
            'all_permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
            'permissions_with_guard' => $user->getAllPermissions()->map(function($p) {
                return $p->name . ' (' . $p->guard_name . ')';
            })->toArray(),
        ]);
        
        // Check if user has backpack API access permission
        if (!$user->hasPermissionTo('backpack.api.access', 'backpack')) {
            Log::warning('Backpack user lacks API access permission', [
                'user_id' => $user->id,
                'email' => $user->email,
                'path' => $request->path(),
                'checked_permission' => 'backpack.api.access',
                'checked_guard' => 'backpack',
            ]);
            return $this->forbidden();
        }

        return $next($request);
    }

    /**
     * Return unauthorized response
     */
    private function unauthorized()
    {
        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'error' => __('backpack::base.unauthorized'),
                'code' => 401
            ], 401);
        }
        
        return redirect()->guest(backpack_url('login'));
    }

    /**
     * Return forbidden response
     */
    private function forbidden()
    {
        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'error' => __('backpack::base.forbidden'),
                'code' => 403
            ], 403);
        }
        
        return redirect()->route(backpack_url('dashboard'))->with('error', __('backpack::base.forbidden'));
    }
}
