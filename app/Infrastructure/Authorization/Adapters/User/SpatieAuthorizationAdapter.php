<?php

namespace App\Infrastructure\Authorization\Adapters\User;

use App\Application\User\Contracts\UserAuthorizationAdapterInterface;
use App\Models\User;

/**
 * Unified authorization adapter using Spatie roles/permissions.
 * Supports both web and backpack guards.
 */
class SpatieAuthorizationAdapter implements UserAuthorizationAdapterInterface
{
    public function isWebAdmin(int $userId): bool
    {
        $user = User::query()->find($userId);
        if (!$user || !method_exists($user, 'hasRole')) { return false; }
        try { return (bool) $user->hasRole('admin'); } catch (\Throwable) { return false; }
    }

    public function isBackpackAdmin(int $userId): bool
    {
        $user = User::query()->find($userId);
        if (!$user || !method_exists($user, 'hasRole')) { return false; }
        try { return (bool) $user->hasRole('admin', 'backpack'); } catch (\Throwable) { return false; }
    }

    public function hasBackpackPermission(int $userId, string $permission): bool
    {
        $user = User::query()->find($userId);
        if (!$user || !method_exists($user, 'hasPermissionTo')) { return false; }
        try { return (bool) $user->hasPermissionTo($permission, 'backpack'); } catch (\Throwable) { return false; }
    }

    public function hasBackpackViewPermission(int $userId, string $entityType): bool
    {
        $user = User::query()->find($userId);
        if (!$user || !method_exists($user, 'hasPermissionTo')) { return false; }
        try { return (bool) $user->hasPermissionTo("can_view_{$entityType}", 'backpack'); } catch (\Throwable) { return false; }
    }

    public function hasCreateEditPermission(int $userId): bool
    {
        $user = User::query()->find($userId);
        if (!$user || !method_exists($user, 'hasPermissionTo')) { return false; }
        try { return (bool) $user->hasPermissionTo('frontend.can_create_edit_product'); } catch (\Throwable) { return false; }
    }

    public function hasFrontendUserRole(int $userId): bool
    {
        $user = User::query()->find($userId);
        if (!$user || !method_exists($user, 'hasRole')) { return false; }
        try { return $user->hasRole('frontend_user') || $user->hasRole('frontend_user_plus'); } catch (\Throwable) { return false; }
    }

    public function canAccessAnyClients(int $userId): bool
    {
        return $this->isWebAdmin($userId) || $this->isBackpackAdmin($userId) || $this->hasBackpackViewPermission($userId, 'client');
    }

    public function canAccessAnySuppliers(int $userId): bool
    {
        return $this->isWebAdmin($userId) || $this->isBackpackAdmin($userId) || $this->hasBackpackViewPermission($userId, 'supplier');
    }
}
