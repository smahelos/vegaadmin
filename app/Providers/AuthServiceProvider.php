<?php

namespace App\Providers;

use App\Application\Auth\Contracts\AuthorizationServiceInterface;
use App\Application\Auth\Services\AuthorizationService;
use App\Infrastructure\Authorization\Policies\Payment\SubscriptionPlanPolicy;
use App\Infrastructure\Authorization\Policies\Payment\SubscriptionPolicy;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
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
        Subscription::class => SubscriptionPolicy::class,
        SubscriptionPlan::class => SubscriptionPlanPolicy::class,
    ];

    /**
     * Composition root of the Auth application context (ADR D-042).
     */
    public function register(): void
    {
        parent::register();

        $this->app->bind(
            AuthorizationServiceInterface::class,
            AuthorizationService::class,
        );
    }

    /**
     * Register any authentication / authorization services
     */
    public function boot(): void
    {
        // Register policies
        $this->registerPolicies();

        // Define macro for multi-guard authentication check
        Auth::macro('checkAny', function (?array $guards = null) {
            $guards = $guards ?: [
                'web',
                'backpack',
                'api',
            ];

            foreach ($guards as $guard) {
                if (Auth::guard($guard)->check()) {
                    return true;
                }
            }

            return false;
        });

        // Define macro for getting user from any guard
        Auth::macro('userFromAny', function (?array $guards = null) {
            $guards = $guards ?: [
                'web',
                'backpack',
                'api',
            ];

            foreach ($guards as $guard) {
                if (Auth::guard($guard)->check()) {
                    return Auth::guard($guard)->user();
                }
            }

            return null;
        });
    }
}
