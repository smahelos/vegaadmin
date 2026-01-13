<?php

namespace App\Infrastructure\Authorization\Listeners;

use App\Domain\User\Services\PermissionLimitResolver;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Events\PermissionAttached;
use Spatie\Permission\Events\PermissionDetached;
use Spatie\Permission\Events\RoleAttached;
use Spatie\Permission\Events\RoleDetached;

/**
 * UELS Permission Change Listener (Infrastructure)
 * 
 * Handles cache invalidation when user permissions or roles change.
 * Kept in Infrastructure to allow Laravel-specific queue interfaces.
 */
class UELSPermissionChangeListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Permission Limit Resolver instance
     */
    private PermissionLimitResolver $resolver;

    /**
     * Create the event listener.
     */
    public function __construct(PermissionLimitResolver $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * Handle permission attached event
     */
    public function handlePermissionAttached(PermissionAttached $event): void
    {
        $this->clearUserCache($event);
        Log::info('UELS: Permission attached, cache cleared', [
            'model_id' => $event->model->id ?? null,
            'permissions' => is_array($event->permissionsOrIds) ? $event->permissionsOrIds : [$event->permissionsOrIds],
            'model_type' => get_class($event->model)
        ]);
    }

    /**
     * Handle permission detached event
     */
    public function handlePermissionDetached(PermissionDetached $event): void
    {
        $this->clearUserCache($event);
        Log::info('UELS: Permission detached, cache cleared', [
            'model_id' => $event->model->id ?? null,
            'permissions' => is_array($event->permissionsOrIds) ? $event->permissionsOrIds : [$event->permissionsOrIds],
            'model_type' => get_class($event->model)
        ]);
    }

    /**
     * Handle role attached event
     */
    public function handleRoleAttached(RoleAttached $event): void
    {
        $this->clearUserCache($event);
        Log::info('UELS: Role attached, cache cleared', [
            'model_id' => $event->model->id ?? null,
            'roles' => is_array($event->rolesOrIds) ? $event->rolesOrIds : [$event->rolesOrIds],
            'model_type' => get_class($event->model)
        ]);
    }

    /**
     * Handle role detached event
     */
    public function handleRoleDetached(RoleDetached $event): void
    {
        $this->clearUserCache($event);
        Log::info('UELS: Role detached, cache cleared', [
            'model_id' => $event->model->id ?? null,
            'roles' => is_array($event->rolesOrIds) ? $event->rolesOrIds : [$event->rolesOrIds],
            'model_type' => get_class($event->model)
        ]);
    }

    /**
     * Clear cache for the affected user
     */
    private function clearUserCache($event): void
    {
        // Check if the model is a User model (not Role)
        if ($event->model instanceof \App\Models\User && $event->model->id) {
            $this->resolver->clearUserCache($event->model->id);
        }
    }

    /**
     * Entry point for Laravel event dispatcher
     */
    public function handle($event): void
    {
        switch (get_class($event)) {
            case PermissionAttached::class:
                $this->handlePermissionAttached($event);
                break;
            case PermissionDetached::class:
                $this->handlePermissionDetached($event);
                break;
            case RoleAttached::class:
                $this->handleRoleAttached($event);
                break;
            case RoleDetached::class:
                $this->handleRoleDetached($event);
                break;
        }
    }
}
