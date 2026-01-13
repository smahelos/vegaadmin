<?php

namespace Tests\Traits;

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

trait CreatesFrontendTestEnvironment
{
    /**
     * Set up frontend test environment with all required permissions and roles
     */
    protected function setUpFrontendTestEnvironment(): void
    {
        // ------------------------------------------------------------------
        // Minimal fallback schema for Spatie permission tables when package
        // migrations are not published in test environment.
        // ------------------------------------------------------------------
        $this->ensurePermissionTables();

        // Ensure required permissions exist for backpack guard
        $requiredFrontendPermissions = [
            'frontend.api.access',
            'frontend.can_create_edit_user',
            'frontend.can_create_edit_supplier', 
            'frontend.can_create_edit_client',
            'frontend.can_create_edit_invoice',
            'frontend.can_create_edit_product',
            'frontend.can_create_edit_expense',
            'frontend.can_create_edit_tax',
            'frontend.can_create_edit_bank',
            'frontend.can_create_edit_payment_method',
            'frontend.can_create_edit_command',
            'frontend.can_create_edit_cron_task',
            'frontend.can_create_edit_status',
            'frontend.can_create_edit_page',
            'frontend.can_create_subscription',
            'frontend.can_cancel_subscription',
            'frontend.can_view_subscription',
            'frontend.can_create_edit_subscription_plan',
            'frontend.can_create_edit_subscription_plan_feature',
        ];

        // Ensure required permissions exist for backpack guard
        $requiredBackendPermissions = [
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
            'can_create_edit_page',
            'can_create_edit_subscription',
            'can_create_edit_subscription_plan',
            'can_create_edit_subscription_plan_feature',
        ];

        // Ensure required API permissions exist for backpack guard
        $requiredApiBackendPermissions = [
            'backpack.access',
            'backpack.api.access',
            'can_view_client'
        ];

        foreach ($requiredFrontendPermissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web'
            ]);
        }

        foreach ($requiredBackendPermissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'backpack'
            ]);
        }

        foreach ($requiredApiBackendPermissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'backpack'
            ]);
        }

        // Ensure admin role exists
        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web'
        ]);

        // Ensure admin role exists
        $backendAdminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'backpack'
        ]);

        // Create unauthorized admin role (without can_view_client etc. permissions)
        $unauthorizedRole = Role::firstOrCreate([
            'name' => 'unauthorized_admin',
            'guard_name' => 'backpack'
        ]);

        // Ensure frontend_user role exists
        $frontendUserRole = Role::firstOrCreate([
            'name' => 'frontend_user',
            'guard_name' => 'web'
        ]);

        // Give admin role all permissions
        $frontendPermissions = Permission::where('guard_name', 'web')->get();
        $adminRole->syncPermissions($frontendPermissions);

        // Create admin user with admin role (if not already set)
        if (!isset($this->adminUser)) {
            $this->adminUser = User::factory()->create([
                'name' => 'Admin User',
                'email' => 'admin@example.com'
            ]);
        }
        $this->adminUser->assignRole($adminRole);


        // Give admin role all permissions
        $backendPermissions = Permission::where('guard_name', 'backpack')->get();
        $backendAdminRole->syncPermissions($backendPermissions);

        // Create admin user with admin role (if not already set)
        if (!isset($this->backendAdminUser)) {
            $this->backendAdminUser = User::factory()->create([
                'name' => 'Backend Admin User',
                'email' => 'backend.admin@example.com'
            ]);
        }
        $this->backendAdminUser->assignRole($backendAdminRole);


        // Give unauthorized admin role basic permissions
        $basicBackpackPermissions = Permission::where('guard_name', 'backpack')
            ->whereIn('name', ['backpack.access', 'backpack.api.access'])
            ->get();
        $unauthorizedRole->syncPermissions($basicBackpackPermissions);

        // Create unauthorized admin user with admin role (if not already set)
        if (!isset($this->unauthorizedAdmin)) {
            $this->unauthorizedAdmin = User::factory()->create([
                'name' => 'Unauthorized Admin',
                'email' => 'unauthorized@example.com'
            ]);
        }
        $this->unauthorizedAdmin->assignRole($unauthorizedRole);

        // Create regular user with limited role (if not already set)
        if (!isset($this->user)) {
            $this->user = User::factory()->create();
        }
        $this->user->assignRole($frontendUserRole);
        // Give frontend_user role minimal API permissions
        $frontendUserRole->syncPermissions(
            Permission::where('guard_name','web')
                ->whereIn('name', ['frontend.api.access','frontend.can_create_edit_invoice'])
                ->get()
        );
        
        // Get frontend.api.access permission if it exists
        $frontendApiAccessPermission = Permission::where('name', 'frontend.api.access')
                                             ->where('guard_name', 'web')
                                             ->first();
        if ($frontendApiAccessPermission) {
            $this->user->givePermissionTo($frontendApiAccessPermission);
        }
    }

    /**
     * Create minimal permission/role tables if migrations not present.
     */
    private function ensurePermissionTables(): void
    {
        // permissions
        if (!Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();
            });
        }
        // roles
        if (!Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();
            });
        }
        // model_has_permissions
        if (!Schema::hasTable('model_has_permissions')) {
            Schema::create('model_has_permissions', function (Blueprint $table) {
                $table->unsignedInteger('permission_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->index(['model_id', 'model_type'], 'model_has_permissions_model_id_model_type_index');
            });
        }
        // model_has_roles
        if (!Schema::hasTable('model_has_roles')) {
            Schema::create('model_has_roles', function (Blueprint $table) {
                $table->unsignedInteger('role_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->index(['model_id', 'model_type'], 'model_has_roles_model_id_model_type_index');
            });
        }
        // role_has_permissions
        if (!Schema::hasTable('role_has_permissions')) {
            Schema::create('role_has_permissions', function (Blueprint $table) {
                $table->unsignedInteger('permission_id');
                $table->unsignedInteger('role_id');
            });
        }
    }
}
