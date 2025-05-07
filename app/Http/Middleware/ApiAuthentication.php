<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class ApiAuthentication
{
    /**
     * Handle authentication for API requests
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check authentication using all possible guards
        $authenticated = false;
        $user = null;

        // First try backpack guard (most likely for admin)
        if (backpack_auth()->check()) {
            $authenticated = true;
            $user = backpack_auth()->user();
            $request->attributes->add(['user' => $user]);
        }
        // Then try web guard
        else if (Auth::guard('web')->check()) {
            $authenticated = true;
            $user = Auth::guard('web')->user();
            $request->attributes->add(['user' => $user]);
        }
        // Finally try sanctum/api guard
        else if (Auth::guard('sanctum')->check()) {
            $authenticated = true;
            $user = Auth::guard('sanctum')->user();
            $request->attributes->add(['user' => $user]);
        }

        // If user is not authenticated, return error
        if (!$authenticated) {
            Log::error('API authentication failed', [
                'path' => $request->path(),
                'ip' => $request->ip()
            ]);
            
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'error' => __('auth.unauthenticated'),
                    'redirect' => $request->is('api/admin/*') ? backpack_url('dashboard') : route('login')
                ], 401);
            }
            
            return redirect()->guest($request->is('admin*') ? backpack_url('login') : route('login'));
        }

        // Refresh session timeout on each API request
        Session::migrate(true);

        return $next($request);
    }
}