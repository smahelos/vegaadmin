<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create frontend permissions with 'web' guard
        $frontendPermissions = [
            'frontend.api.access' => 'Access to frontend API endpoints',
            'frontend.api.clients' => 'Access to client management API',
            'frontend.api.suppliers' => 'Access to supplier management API',
            'frontend.api.invoices' => 'Access to invoice management API',
            'frontend.api.statistics' => 'Access to statistics API',
            'frontend.api.extended' => 'Extended frontend API access',
        ];

        foreach ($frontendPermissions as $name => $description) {
            Permission::firstOrCreate(
                ['name' => $name],
                ['guard_name' => 'web']
            );
        }

        // Create backpack permissions with 'backpack' guard
        $backpackPermissions = [
            'backpack.access' => 'Access to Backpack admin panel',
            'backpack.api.access' => 'Access to admin API endpoints',
            'backpack.api.clients' => 'Access to admin client management API',
            'backpack.api.suppliers' => 'Access to admin supplier management API',
            'backpack.api.invoices' => 'Access to admin invoice management API',
        ];

        foreach ($backpackPermissions as $name => $description) {
            Permission::firstOrCreate(
                ['name' => $name],
                ['guard_name' => 'backpack']
            );
        }

        // Create or update roles and assign permissions
        $this->createRoles();
    }

    /**
     * Create roles and assign permissions
     */
    private function createRoles(): void
    {
        // Admin role with backpack guard - all permissions from both guards
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'backpack']);
        $adminRole->syncPermissions(Permission::where('guard_name', 'backpack')->get());

        // Backend user role with backpack guard
        $backendRole = Role::firstOrCreate(['name' => 'backend_user', 'guard_name' => 'backpack']);
        $backendRole->syncPermissions([
            'backpack.access',
            'backpack.api.access',
            'backpack.api.clients',
            'backpack.api.suppliers',
            'backpack.api.invoices',
        ]);

        // Frontend user role with web guard
        $frontendRole = Role::firstOrCreate(['name' => 'frontend_user', 'guard_name' => 'web']);
        $frontendRole->syncPermissions([
            'frontend.api.access',
            'frontend.api.clients',
            'frontend.api.suppliers',
            'frontend.api.invoices',
            'frontend.api.statistics',
        ]);

        // Frontend user plus role with web guard
        $frontendPlusRole = Role::firstOrCreate(['name' => 'frontend_user_plus', 'guard_name' => 'web']);
        $frontendPlusRole->syncPermissions([
            'frontend.api.access',
            'frontend.api.clients',
            'frontend.api.suppliers',
            'frontend.api.invoices',
            'frontend.api.statistics',
            'frontend.api.extended',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove roles
        $roleNames = ['admin', 'backend_user', 'frontend_user', 'frontend_user_plus'];
        foreach ($roleNames as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->delete();
            }
        }

        // Remove permissions
        $permissionNames = [
            'frontend.api.access',
            'frontend.api.clients',
            'frontend.api.suppliers',
            'frontend.api.invoices',
            'frontend.api.statistics',
            'backpack.access',
            'backpack.api.access',
            'backpack.api.clients',
            'backpack.api.suppliers',
            'backpack.api.invoices',
            'frontend.api.extended',
        ];
        
        foreach ($permissionNames as $permissionName) {
            $permission = Permission::where('name', $permissionName)->first();
            if ($permission) {
                $permission->delete();
            }
        }
    }
};
