<?php

namespace App\Infrastructure\Services\User;

use App\Domain\User\Contracts\UserPermissionServiceInterface;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Infrastructure implementation of UserPermissionServiceInterface.
 *
 * Handles Eloquent operations and permission checking using Spatie Permission package.
 * This keeps all Framework-specific logic out of Domain layer.
 */
class LaravelUserPermissionService implements UserPermissionServiceInterface
{
    public function getUserPermissions(int $userId, string $guard): array
    {
        $user = User::find($userId);

        if (!$user) {
            Log::debug('UserPermissionService: User not found', [
                'user_id' => $userId,
            ]);

            return [];
        }

        $permissions = $user->getAllPermissions();
        $permissions = $permissions->where('guard_name', $guard);
        $permissionNames = $permissions->pluck('name');
        $permissionNames = $permissionNames->unique();
        $permissionNames = $permissionNames->values();
        $permissionNames = $permissionNames->toArray();

        Log::debug(
            'UserPermissionService: Retrieved user permissions',
            [
                'user_id' => $userId,
                'permissions_count' => count($permissionNames),
            ],
        );

        return $permissionNames;
    }
}
