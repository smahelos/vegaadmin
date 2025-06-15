<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

/**
 * @deprecated This middleware is deprecated. Use the new permission-based middleware instead:
 * - RequireBackpackApiAccess for admin API access with proper permission checking
 * 
 * This class will be removed in a future version.
 */
class CheckIfAdmin
{
    /**
     * Answer to unauthorized access request
     *
     * @param \Illuminate\Http\Request $request
     * @param bool $isAuthenticated
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    private function respondToUnauthorizedRequest($request, $isAuthenticated = false)
    {
        if ($request->ajax() || $request->wantsJson()) {
            $statusCode = $isAuthenticated ? 403 : 401;
            $errorKey = $isAuthenticated ? 'backpack.base.unauthorized' : 'users.auth.unauthenticated';
            
            return response()->json([
                'error' => __($errorKey)
            ], $statusCode);
        } else {
            return redirect()->guest(backpack_url('login'));
        }
    }

    /**
     * Check if user is admin and handle unauthorized access
     */
    public function handle($request, Closure $next)
    {
        $isAuthenticated = false;
        $isAdmin = false;
        
        // Check backpack auth first
        if (backpack_auth()->check()) {
            $isAuthenticated = true;
            // For Backpack authenticated users, always consider them as admin
            // since they have access to the admin panel already
            $isAdmin = true;
        }
        
        // If not admin via backpack, check web auth
        if (!$isAdmin && Auth::guard('web')->check()) {
            $isAuthenticated = true;
            $user = Auth::guard('web')->user();
            if ($user->hasRole('admin')) {
                $isAdmin = true;
            }
        }
        
        // If still not admin, check sanctum auth
        if (!$isAdmin && Auth::guard('sanctum')->check()) {
            $isAuthenticated = true;
            $user = Auth::guard('sanctum')->user();
            if ($user->hasRole('admin')) {
                $isAdmin = true;
            }
        }
        
        // If not admin (whether authenticated or not), deny access with 401
        // For admin endpoints, non-admin users are treated as unauthenticated
        if (!$isAdmin) {
            if ($isAuthenticated) {
                Log::warning('Non-admin user attempted to access admin area', [
                    'user_id' => Auth::id() ?? backpack_user()?->id ?? 'unknown',
                    'path' => $request->path()
                ]);
            }
            return $this->respondToUnauthorizedRequest($request, false);
        }
        
        return $next($request);
    }
}
