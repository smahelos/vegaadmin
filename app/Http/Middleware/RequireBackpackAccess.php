<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Middleware to check if user has admin access via roles with 'backpack' guard
 */
class RequireBackpackAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if user is authenticated with backpack guard
        if (!backpack_auth()->check()) {
            return $this->respondToUnauthorizedRequest($request, false);
        }

        $user = backpack_user();
        
        // Debug: Log detailed user info for troubleshooting
        Log::info('RequireBackpackAccess middleware - User details', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'all_roles' => $user->roles->map(function($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name, 
                    'guard_name' => $role->guard_name
                ];
            })->toArray(),
            'backpack_roles' => $user->roles()->where('guard_name', 'backpack')->get()->map(function($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'permissions' => $role->permissions->pluck('name')->toArray()
                ];
            })->toArray(),
            'direct_permissions' => $user->permissions->where('guard_name', 'backpack')->pluck('name')->toArray(),
            'has_backpack_access_via_role' => $user->hasRole(['admin', 'backend_user'], 'backpack'),
            'has_backpack_access_permission' => $user->hasPermissionTo('backpack.access', 'backpack'),
            'can_backpack_access' => $user->can('backpack.access')
        ]);
        
        // Check if user has any role with 'backpack' guard
        $hasBackpackRole = $user->roles()
            ->where('guard_name', 'backpack')
            ->exists();

        if (!$hasBackpackRole) {
            Log::warning('User without backpack roles attempted to access admin area', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'path' => $request->path(),
                'user_roles' => $user->roles->pluck('name', 'guard_name')->toArray()
            ]);
            
            return $this->respondToUnauthorizedRequest($request, true);
        }

        // If user has backpack role, they should have access
        // But let's also verify the permission as additional security layer
        if (!$user->hasPermissionTo('backpack.access', 'backpack')) {
            Log::warning('User with backpack role but without backpack.access permission attempted to access admin area', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'path' => $request->path(),
                'backpack_roles' => $user->roles()->where('guard_name', 'backpack')->pluck('name')->toArray(),
                'role_permissions' => $user->roles()->where('guard_name', 'backpack')->with('permissions')->get()->map(function($role) {
                    return [
                        'role' => $role->name,
                        'permissions' => $role->permissions->where('guard_name', 'backpack')->pluck('name')->toArray()
                    ];
                })->toArray()
            ]);
            
            return $this->respondToUnauthorizedRequest($request, true);
        }

        return $next($request);
    }

    /**
     * Handle unauthorized access
     *
     * @param \Illuminate\Http\Request $request
     * @param bool $isAuthenticated
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    private function respondToUnauthorizedRequest(Request $request, bool $isAuthenticated = false)
    {
        if ($request->ajax() || $request->wantsJson()) {
            $statusCode = $isAuthenticated ? 403 : 401;
            $message = $isAuthenticated 
                ? trans('backpack::base.unauthorized') 
                : trans('backpack::base.please_login');
            
            return response()->json([
                'error' => $message,
                'code' => $statusCode
            ], $statusCode);
        }

        if ($isAuthenticated) {
            // User is logged in but doesn't have proper roles 
            // Log them out and redirect to login with error message
            backpack_auth()->logout();
            
            return redirect()->route('backpack.auth.login')
                ->withErrors([
                    'email' => trans('admin.auth.no_backpack_role')
                ]);
        }

        // User is not logged in - redirect to login
        return redirect()->guest(backpack_url('login'));
    }
}
