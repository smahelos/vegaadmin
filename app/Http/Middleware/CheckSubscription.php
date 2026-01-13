<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip middleware for guests
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();
        
        // Check if user has active subscription
        if (!$user->hasActiveSubscription()) {
            // Allow access to subscription pages
            if ($request->routeIs('subscriptions.*') || $request->routeIs('payment.*')) {
                return $next($request);
            }

            // Redirect to subscription page for other protected routes
            return redirect()->route('subscriptions.index', ['locale' => app()->getLocale()])
                ->with('warning', __('subscription.subscription_required'));
        }

        return $next($request);
    }
}
