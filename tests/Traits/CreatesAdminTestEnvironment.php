<?php

namespace Tests\Traits;

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

trait CreatesAdminTestEnvironment
{
    /**
     * Set up admin test environment with all required permissions and roles
     */
    protected function setUpAdminTestEnvironment(): void
    {
        // Ensure required permissions exist for backpack guard
        $requiredPermissions = [
            'can_create_edit_user',
            'can_create_edit_supplier', 
            'can_create_edit_client',
            'can_create_edit_invoice',
            'can_create_edit_product',
            'can_create_edit_expense',
            'can_create_edit_tax',
            'can_create_edit_bank',
            'can_create_edit_payment_method',
            'can_create_edit_command',
            'can_create_edit_cron_task',
            'can_create_edit_status',
            'can_create_edit_page',                    // Missing permission for menu
            'can_create_edit_subscription_plan',
            'can_create_edit_subscription',
            'can_create_edit_subscription_plan_feature',
            'can_configure_system',
            'backpack.access',
            'backpack.api.access',
            'backpack.api.clients',
            'backpack.api.suppliers',
            'backpack.api.invoices',
            'backpack.api.products',
            'backpack.api.extended'
        ];

        foreach ($requiredPermissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'backpack'
            ]);
        }

        // Ensure admin role exists
        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'backpack'
        ]);

        // Ensure backend_user role exists
        $backendUserRole = Role::firstOrCreate([
            'name' => 'backend_user', 
            'guard_name' => 'backpack'
        ]);

        // Give admin role all permissions
        $permissions = Permission::where('guard_name', 'backpack')->get();
        $adminRole->syncPermissions($permissions);

        // Create admin user with admin role (if not already set)
        if (!isset($this->adminUser)) {
            $this->adminUser = User::factory()->create();
        }
        $this->adminUser->assignRole($adminRole);

        // Create regular user with limited role (if not already set)
        if (!isset($this->regularUser)) {
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
}
