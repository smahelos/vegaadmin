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
        // Ensure a flag exists to mark that frontend session middleware has run
        if (!session()->has('frontend_session_state')) {
            session(['frontend_session_state' => true]);
        }

        // Regenerate session ID on first pass and then only after the defined interval
        // Keep session data across regeneration
        $now = time();
        $lastRegenerated = (int) session('frontend_session_last_regenerated', 0);
        $intervalSeconds = 15 * 60; // 15 minutes

        if ($lastRegenerated === 0 || ($now - $lastRegenerated) >= $intervalSeconds) {
            // Regenerate session ID without destroying existing data
            Session::migrate(false);
            session(['frontend_session_last_regenerated' => $now]);
        }

        return $next($request);
    }
}
