<?php

namespace App\Providers;

use App\Domain\Analytics\Listeners\InvalidateOwnerDashboard;
use App\Domain\Party\Events\BusinessArchived;
use App\Domain\Party\Events\BusinessCreated;
use App\Domain\Party\Events\BusinessRestored;
use App\Domain\Party\Events\BusinessUpdated;
use App\Domain\Party\Events\ContactArchived;
use App\Domain\Party\Events\ContactCreated;
use App\Domain\Party\Events\ContactRestored;
use App\Domain\Party\Events\ContactUpdated;
use App\Domain\Party\Events\DefaultBusinessChanged;
use App\Domain\Shared\Events\FormDataChanged;
use App\Domain\Shared\Listeners\InvalidateFormDataCache;
use App\Domain\User\Events\UserDataChanged;
use App\Domain\User\Listeners\InvalidateUserCache;
use App\Infrastructure\Authorization\Listeners\UELSPermissionChangeListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
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
        BusinessArchived::class => [
            InvalidateOwnerDashboard::class,
        ],
        BusinessCreated::class => [
            InvalidateOwnerDashboard::class,
        ],
        BusinessRestored::class => [
            InvalidateOwnerDashboard::class,
        ],
        BusinessUpdated::class => [
            InvalidateOwnerDashboard::class,
        ],
        ContactArchived::class => [
            InvalidateOwnerDashboard::class,
        ],
        ContactCreated::class => [
            InvalidateOwnerDashboard::class,
        ],
        ContactRestored::class => [
            InvalidateOwnerDashboard::class,
        ],
        ContactUpdated::class => [
            InvalidateOwnerDashboard::class,
        ],
        DefaultBusinessChanged::class => [
            InvalidateOwnerDashboard::class,
        ],
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
        // Model observers are registered in the bounded context providers (ADR D-042).
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
