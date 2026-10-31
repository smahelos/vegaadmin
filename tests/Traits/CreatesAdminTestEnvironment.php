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
        $permissions = Permission::where('guard_name', PermissionCatalog::BACKPACK)->whereIn('name', PermissionCatalog::PERMISSIONS[PermissionCatalog::BACKPACK])->get();
        $adminRole->syncPermissions($permissions);

        // Create admin user with admin role (if not already set)
        if (! isset($this->adminUser)) {
            $this->adminUser = User::factory()->create();
        }
        $this->adminUser->assignRole($adminRole);

        // Create regular user with limited role (if not already set)
        if (! isset($this->regularUser)) {
            $this->regularUser = User::factory()->create();
        }
        $this->regularUser->assignRole($backendUserRole);

        // Get backpack.access permission if it exists
        $backpackAccessPermission = Permission::where('name', 'backpack.access')
            ->where('guard_name', 'backpack')
            ->first();
        if ($backpackAccessPermission) {
            $this->regularUser->givePermissionTo($backpackAccessPermission);
        }
    }

    /**
     * Take the admin role's bypass of the entity limits (can_create_{entity}_unlimited) away,
     * so that admin creation is limited like any user without the bypass.
     */
    protected function revokeUnlimitedAccess(string $entityType): void
    {
        Role::findByName('admin', 'backpack')->revokePermissionTo("can_create_{$entityType}_unlimited");
    }
}
