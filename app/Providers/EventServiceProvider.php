<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Domain\User\Events\UserDataChanged;
use App\Domain\Shared\Events\FormDataChanged;
use App\Domain\User\Listeners\InvalidateUserCache;
use App\Domain\Shared\Listeners\InvalidateFormDataCache;
use App\Infrastructure\Authorization\Listeners\UELSPermissionChangeListener;
use Spatie\Permission\Events\PermissionAttached;
use Spatie\Permission\Events\PermissionDetached;
use Spatie\Permission\Events\RoleAttached;
use Spatie\Permission\Events\RoleDetached;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        UserDataChanged::class => [
            InvalidateUserCache::class,
        ],
        FormDataChanged::class => [
            InvalidateFormDataCache::class,
        ],
        // UELS Permission Change Events
        PermissionAttached::class => [
            UELSPermissionChangeListener::class,
        ],
        PermissionDetached::class => [
            UELSPermissionChangeListener::class,
        ],
        RoleAttached::class => [
            UELSPermissionChangeListener::class,
        ],
        RoleDetached::class => [
            UELSPermissionChangeListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
    parent::boot();
    // Model observers now registered in InfrastructureServiceProvider.
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
