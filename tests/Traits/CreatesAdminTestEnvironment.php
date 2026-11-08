<?php

declare(strict_types=1);

namespace Tests\Traits;

use App\Infrastructure\Authorization\PermissionCatalog;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

trait CreatesAdminTestEnvironment
{
    protected User $adminUser;

    protected User $regularUser;

    /**
     * Set up admin test environment with all required permissions and roles
     */
    protected function setUpAdminTestEnvironment(): void
    {
        // Ensure admin role exists
        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'backpack',
        ]);

        // Ensure backend_user role exists
        $backendUserRole = Role::firstOrCreate([
            'name' => 'backend_user',
            'guard_name' => 'backpack',
        ]);

        // Give admin role all permissions
        $permissionQuery = Permission::where('guard_name', PermissionCatalog::BACKPACK);
        $permissionQuery->whereIn('name', PermissionCatalog::PERMISSIONS[PermissionCatalog::BACKPACK]);
        $permissions = $permissionQuery->get();
        $adminRole->syncPermissions($permissions);

        // Create admin user with admin role (if not already set)
        $userFactory = User::factory();
        if (!isset($this->adminUser)) {
            $this->adminUser = $userFactory->create();
        }
        $this->adminUser->assignRole($adminRole);
        // The admin tests create documents and products for these accounts, and a plan role allows that
        $planRole = Role::findByName('frontend_user', 'web');
        $this->adminUser->assignRole($planRole);

        // Create regular user with limited role (if not already set)
        if (!isset($this->regularUser)) {
            $this->regularUser = $userFactory->create();
        }
        $this->regularUser->assignRole($backendUserRole);
        $this->regularUser->assignRole($planRole);

        // Get backpack.access permission if it exists
        $permissionQuery = Permission::where('name', 'backpack.access');
        $permissionQuery->where('guard_name', 'backpack');
        $backpackAccessPermission = $permissionQuery->first();
        if ($backpackAccessPermission) {
            $this->regularUser->givePermissionTo($backpackAccessPermission);
        }
    }
}
