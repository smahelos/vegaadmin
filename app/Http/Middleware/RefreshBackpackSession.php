<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class RefreshBackpackSession
{
    /**
     * Refresh session for admin panel requests
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Set unique identifier for backpack session
        if (!session()->has('backpack_session_state')) {
            session(['backpack_session_state' => true]);
        }

        // Refresh session expiration
        Session::migrate(true);
        
        return $next($request);
    }
}