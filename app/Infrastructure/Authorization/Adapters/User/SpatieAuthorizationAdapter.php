<?php

namespace App\Infrastructure\Authorization\Adapters\User;

use App\Application\User\Contracts\UserAuthorizationAdapterInterface;
use App\Domain\User\Enums\Permission;
use App\Models\User;

/**
 * Unified authorization adapter using Spatie roles/permissions.
 * Supports both web and backpack guards.
 */
class SpatieAuthorizationAdapter implements UserAuthorizationAdapterInterface
{
    public function hasBackpackPermission(int $userId, Permission $permission): bool
    {
        $query = User::query();
        $user = $query->find($userId);
        if ($user === null) {
            return false;
        }

        return $user->checkPermissionTo($permission, 'backpack');
    }
}
