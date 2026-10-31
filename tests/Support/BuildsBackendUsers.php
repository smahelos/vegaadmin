<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Admin users with the backend role, admin access and only the given permissions.
 */
trait BuildsBackendUsers
{
    private function backendUser(string ...$permissions): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findByName('backend_user', 'backpack'));
        foreach (['backpack.access', ...$permissions] as $permission) {
            $user->givePermissionTo(Permission::findByName($permission, 'backpack'));
        }

        return $user;
    }
}
