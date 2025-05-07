<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services
     */
    public function boot(): void
    {
        // Define macro for multi-guard authentication check
        Auth::macro('checkAny', function (array $guards = null) {
            $guards = $guards ?: ['web', 'backpack', 'api'];
            
            foreach ($guards as $guard) {
                if (Auth::guard($guard)->check()) {
                    return true;
                }
            }
            
            return false;
        });
        
        // Define macro for getting user from any guard
        Auth::macro('userFromAny', function (array $guards = null) {
            $guards = $guards ?: ['web', 'backpack', 'api'];
            
            foreach ($guards as $guard) {
                if (Auth::guard($guard)->check()) {
                    return Auth::guard($guard)->user();
                }
            }
            
            return null;
        });
    }
}