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
    public function hasBackpackPermission(int $userId, string $permission): bool
    {
        $query = User::query();
        $user = $query->find($userId);
        if (!$user || !method_exists($user, 'hasPermissionTo')) {
            return false;
        }
        try {
            return (bool) $user->hasPermissionTo($permission, 'backpack');
        } catch (\Throwable) {
            return false;
        }
    }
}
