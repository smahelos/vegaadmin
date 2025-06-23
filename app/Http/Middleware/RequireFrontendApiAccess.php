<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Frontend API access middleware
 * Only works with web guard authentication
 */
class RequireFrontendApiAccess
{
    /**
     * Handle frontend API access check
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only check web guard - never mix with backpack
        if (!Auth::guard('web')->check()) {
            return $this->unauthorized();
        }

        $user = Auth::guard('web')->user();
        
        // Check if user has frontend API access permission
        if (!$user->can('frontend.api.access')) {
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
                'error' => __('users.auth.unauthenticated'),
                'code' => 401
            ], 401);
        }
        
        return redirect()->guest(route('frontend.login', ['locale' => app()->getLocale()]));
    }

    /**
     * Return forbidden response
     */
    private function forbidden()
    {
        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'error' => __('users.auth.forbidden'),
                'code' => 403
            ], 403);
        }
        
        return redirect()->route('frontend.dashboard', ['locale' => app()->getLocale()])->with('error', __('users.auth.forbidden'));
    }
}
