<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class TestingDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create basic permissions for users
        $this->createBasicPermissions();
        
        // Create roles and assign permissions
        $this->createRoles();
        
        // Create test users with different roles
        $this->createTestUsers();
    }
    
    /**
     * Create basic permissions needed for testing
     */
    private function createBasicPermissions(): void
    {
        // User permissions (backpack)
        Permission::firstOrCreate(['name' => 'can_view_user', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'can_create_user', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'can_update_user', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'can_delete_user', 'guard_name' => 'backpack']);
        
        // Client permissions (backpack)
        Permission::firstOrCreate(['name' => 'can_view_client', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'can_create_client', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'can_update_client', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'can_delete_client', 'guard_name' => 'backpack']);
        
        // Client permissions (frontend)
        Permission::firstOrCreate(['name' => 'frontend.can_create_edit_client', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'frontend.can_view_client', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'frontend.can_delete_client', 'guard_name' => 'web']);

        // Supplier permissions (backpack)
        Permission::firstOrCreate(['name' => 'can_view_supplier', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'can_create_supplier', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'can_update_supplier', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'can_delete_supplier', 'guard_name' => 'backpack']);
        
        // Supplier permissions (frontend)
        Permission::firstOrCreate(['name' => 'frontend.can_create_edit_supplier', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'frontend.can_view_supplier', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'frontend.can_delete_supplier', 'guard_name' => 'web']);

        // Invoice permissions (backpack)
        Permission::firstOrCreate(['name' => 'can_view_invoice', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'can_create_invoice', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'can_update_invoice', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'can_delete_invoice', 'guard_name' => 'backpack']);

        // Invoice permissions (frontend)
        Permission::firstOrCreate(['name' => 'frontend.can_create_edit_invoice', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'frontend.can_view_invoice', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'frontend.can_delete_invoice', 'guard_name' => 'web']);

        // Product permissions (backpack)
        Permission::firstOrCreate(['name' => 'can_view_product', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'can_create_product', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'can_update_product', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'can_delete_product', 'guard_name' => 'backpack']);
        
        // Product permissions (frontend)
        Permission::firstOrCreate(['name' => 'frontend.can_create_edit_product', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'frontend.can_view_product', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'frontend.can_delete_product', 'guard_name' => 'web']);
        
        // Api permissions (backpack)
        Permission::firstOrCreate(['name' => 'backpack.access', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'backpack.api.access', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'backpack.api.clients', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'backpack.api.suppliers', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'backpack.api.invoices', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'backpack.api.products', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'backpack.api.extended', 'guard_name' => 'backpack']);
        
        // Api permissions (frontend)
        Permission::firstOrCreate(['name' => 'frontend.api.access', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'frontend.api.clients', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'frontend.api.suppliers', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'frontend.api.invoices', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'frontend.api.products', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'frontend.api.statistics', 'guard_name' => 'web']);
    }
    
    /**
     * Create roles with assigned permissions
     */
    private function createRoles(): void
    {
        // Admin role - has all backpack permissions (guard: backpack)
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'backpack']);
        $adminRole->givePermissionTo(Permission::where('guard_name', 'backpack')->get());
        
        // Backend user role - has limited backpack permissions (guard: backpack)
        $backendUserRole = Role::firstOrCreate(['name' => 'backend_user', 'guard_name' => 'backpack']);
        $backendUserRole->givePermissionTo([
            'backpack.access',
            'can_view_client', 'can_create_client', 'can_update_client',
            'can_view_supplier', 'can_create_supplier', 'can_update_supplier',
            'can_view_invoice', 'can_create_invoice', 'can_update_invoice',
            'can_view_product', 'can_create_product', 'can_update_product',
        ]);
        
        // Frontend user role - has basic frontend permissions (guard: web)
        $frontendUserRole = Role::firstOrCreate(['name' => 'frontend_user', 'guard_name' => 'web']);
        $frontendUserRole->givePermissionTo([
            'frontend.can_view_client', 'frontend.can_create_edit_client',
            'frontend.can_view_supplier', 'frontend.can_create_edit_supplier',
            'frontend.can_view_invoice', 'frontend.can_create_edit_invoice',
            'frontend.can_view_product', 'frontend.can_create_edit_product',
            'frontend.api.access', 'frontend.api.clients', 'frontend.api.suppliers', 
            'frontend.api.invoices', 'frontend.api.products', 'frontend.api.statistics'
        ]);
        
        // Frontend user plus role - has enhanced frontend permissions (guard: web)
        $frontendUserPlusRole = Role::firstOrCreate(['name' => 'frontend_user_plus', 'guard_name' => 'web']);
        $frontendUserPlusRole->givePermissionTo(Permission::where('guard_name', 'web')->get());
        
        // Legacy user role for tests - same as frontend_user
        $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        $userRole->givePermissionTo([
            'frontend.can_create_edit_client', 'frontend.can_view_client', 'frontend.can_delete_client',
            'frontend.can_create_edit_supplier', 'frontend.can_view_supplier', 'frontend.can_delete_supplier',
            'frontend.can_create_edit_invoice', 'frontend.can_view_invoice', 'frontend.can_delete_invoice',
            'frontend.can_create_edit_product', 'frontend.can_view_product', 'frontend.can_delete_product',
            'frontend.api.access', 'frontend.api.clients', 'frontend.api.suppliers', 'frontend.api.invoices',
            'frontend.api.products', 'frontend.api.statistics'
        ]);
        
        // Viewer role - can only view frontend data
        $viewerRole = Role::firstOrCreate(['name' => 'viewer', 'guard_name' => 'web']);
        $viewerRole->givePermissionTo([
            'frontend.can_view_client',
            'frontend.can_view_supplier',
            'frontend.can_view_invoice',
            'frontend.can_view_product',
        ]);
    }
    
    /**
     * Create test users with different roles
     */
    private function createTestUsers(): void
    {
        // Create admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        
        // Clear ALL existing roles and permissions for admin user first!
        $admin->roles()->detach();
        $admin->permissions()->detach();
        
        // Assign ONLY backpack admin role - no web permissions!
        $adminRole = Role::where('name', 'admin')->where('guard_name', 'backpack')->first();
        if ($adminRole) {
            $admin->assignRole($adminRole);
        }
        
        // Create regular user
        $user = User::firstOrCreate(
            ['email' => 'user@example.com'],
            [
                'name' => 'Regular User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $user->assignRole('user');
        
        // Create viewer user
        $viewer = User::firstOrCreate(
            ['email' => 'viewer@example.com'],
            [
                'name' => 'Viewer User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $viewer->assignRole('viewer');
        
        // Create additional test users for various testing scenarios
        User::factory()->count(10)->create()->each(function ($user) {
            $user->assignRole('user');
        });
    }
}
