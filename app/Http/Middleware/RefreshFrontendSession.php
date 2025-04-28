<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class RefreshFrontendSession
{
    /**
     * Refresh session for frontend requests
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Set unique identifier for frontend session
        if (!session()->has('frontend_session_state')) {
            session(['frontend_session_state' => true]);
        }

        // Refresh session expiration on each request
        Session::migrate(true);
        
        return $next($request);
    }
}