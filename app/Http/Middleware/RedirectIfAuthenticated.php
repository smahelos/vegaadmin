<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Redirect if user is already authenticated
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            // Check if user is authenticated
            if (Auth::guard($guard)->check()) {
                // If request is to admin area and using backpack guard
                if ($request->is('admin*') || $guard === 'backpack') {
                    return redirect(backpack_url('dashboard'));
                }
                
                // For frontend guard or without specified guard
                if ($guard === 'web' || $guard === null) {
                    // Prioritize redirection to dashboard if exists
                    return redirect()->route('frontend.dashboard', ['locale' => app()->getLocale()]);
                }
            }
        }

        return $next($request);
    }
}
