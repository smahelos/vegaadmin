<?php

namespace App\Providers;

use App\Infrastructure\Authorization\Policies\Payment\SubscriptionPlanPolicy;
use App\Infrastructure\Authorization\Policies\Payment\SubscriptionPolicy;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

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
     * Register any authentication / authorization services
     */
    public function boot(): void
    {
        // Register policies
        $this->registerPolicies();
    }
}
