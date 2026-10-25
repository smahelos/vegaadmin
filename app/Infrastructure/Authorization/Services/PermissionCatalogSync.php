<?php

namespace App\Infrastructure\Authorization\Services;

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
        Model::withoutEvents(function () use ($permissionClass, $roleClass): void {
            $permissions = [];
            foreach (PermissionCatalog::PERMISSIONS as $guard => $names) {
                foreach ($names as $name) {
                    $permissions[$guard][$name] = $permissionClass::firstOrCreate(['name' => $name, 'guard_name' => $guard]);
                }
            }

            $roleClass::firstOrCreate(['name' => PermissionCatalog::ADMIN_ROLE, 'guard_name' => PermissionCatalog::BACKPACK])
                ->givePermissionTo(array_values($permissions[PermissionCatalog::BACKPACK]));

            foreach (PermissionCatalog::ROLES as $guard => $roles) {
                foreach ($roles as $role => $names) {
                    $roleClass::firstOrCreate(['name' => $role, 'guard_name' => $guard])
                        ->givePermissionTo(array_map(fn (string $name) => $permissions[$guard][$name], $names));
                }
            }
        });

        $this->registrar->forgetCachedPermissions();
    }
}
