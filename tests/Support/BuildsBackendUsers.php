<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\User\Enums\Permission;
use App\Models\Permission as PermissionModel;
use App\Models\User;
use Spatie\Permission\Models\Role;

/**
 * Admin users with the backend role, admin access and only the given permissions.
 */
trait BuildsBackendUsers
{
    private function backendUser(Permission ...$permissions): User
    {
        $userFactory = User::factory();
        $user = $userFactory->create();
        $user->assignRole(
            Role::findByName('backend_user', 'backpack'),
        );
        foreach ([Permission::BACKPACK_ACCESS, ...$permissions] as $permission) {
            $user->givePermissionTo(
                PermissionModel::findByName($permission->value, 'backpack'),
            );
        }

        return $user;
    }
}
