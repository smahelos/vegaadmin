<?php

namespace App\Infrastructure\Authorization\Services;

use App\Domain\User\Enums\Permission;
use App\Infrastructure\Authorization\PermissionCatalog;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\PermissionRegistrar;

/**
 * Writes PermissionCatalog to the database: creates missing permissions and roles, gives the
 * admin role every backpack permission and the system roles their defaults. Additive only:
 * nothing granted through PermissionManager is removed.
 */
final class PermissionCatalogSync
{
    public function __construct(private readonly PermissionRegistrar $registrar) {}

    public function sync(): void
    {
        $permissionClass = $this->registrar->getPermissionClass();
        $roleClass = $this->registrar->getRoleClass();

        // Without model events: App\Models\Permission clears the whole cache on every save
        Model::withoutEvents(
            function () use ($permissionClass, $roleClass): void {
                $permissions = [];
                foreach (PermissionCatalog::PERMISSIONS as $guard => $cases) {
                    foreach ($cases as $case) {
                        $permissions[$guard][$case->value] = $permissionClass::firstOrCreate(
                            ['name' => $case->value, 'guard_name' => $guard],
                        );
                    }
                }

                $adminRole = $roleClass::firstOrCreate(
                    ['name' => PermissionCatalog::ADMIN_ROLE, 'guard_name' => PermissionCatalog::BACKPACK],
                );
                $adminRole->givePermissionTo(
                    array_values($permissions[PermissionCatalog::BACKPACK]),
                );

                foreach (PermissionCatalog::ROLES as $guard => $roles) {
                    foreach ($roles as $role => $cases) {
                        $roleModel = $roleClass::firstOrCreate(['name' => $role, 'guard_name' => $guard]);
                        $roleModel->givePermissionTo(
                            array_map(fn (Permission $case) => $permissions[$guard][$case->value], $cases),
                        );
                    }
                }
            },
        );

        $this->registrar->forgetCachedPermissions();
    }
}
