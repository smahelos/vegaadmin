<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when not authenticated
     */
    protected function redirectTo(Request $request): ?string
    {
        if (!$request->expectsJson()) {
            // Distinguish between backend and frontend based on URL
            if ($request->is('admin*')) {
                // If admin section, redirect to backpack login
                return backpack_url('login');
            }
            
            // For frontend use standard login route
            return route('frontend.login', ['locale' => app()->getLocale()]);
        }
        
        return null;
    }
}
