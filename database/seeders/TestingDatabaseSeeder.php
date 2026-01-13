<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Status;
use App\Models\StatusCategory;
use App\Models\ArtisanCommandCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Domain\Shared\Status\ValueObjects\StatusCode;

class TestingDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Just in testing environment
        if (! app()->environment('testing')) {
            return;
        }

        // Create basic permissions for users
        $this->createBasicPermissions();

        // Create roles and assign permissions
        $this->createRoles();

        // Create test users with different roles
        $this->createTestUsers();

        // Create status categories and statuses
        $this->setupStatusCategories();

        // Create archive policies for testing
        $this->createArchivePolicies();

        // Create Artisan Comand Category
        $this->createArtisanCommandCategory();
    }

    /**
     * Create basic permissions needed for testing based on production data
     */
    private function createBasicPermissions(): void
    {
        // Core Backpack permissions
        Permission::firstOrCreate(['name' => 'backpack.access', 'guard_name' => 'backpack']);

        // API permissions (backpack)
        Permission::firstOrCreate(['name' => 'backpack.api.access', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'backpack.api.clients', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'backpack.api.extended', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'backpack.api.invoices', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'backpack.api.products', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'backpack.api.suppliers', 'guard_name' => 'backpack']);

        // System configuration permissions (backpack)
        Permission::firstOrCreate(['name' => 'can_configure_system', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'can_create_edit_bank', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'can_create_edit_command', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'can_create_edit_cron_task', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'can_create_edit_page', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'can_create_edit_payment_method', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'can_create_edit_status', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'can_create_edit_tax', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'can_view_payment_method', 'guard_name' => 'backpack']);

        // User permissions (backpack)
        Permission::firstOrCreate(['name' => 'can_create_edit_user', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'can_create_user', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'can_delete_user', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'can_update_user', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'can_view_user', 'guard_name' => 'backpack']);

        // Client permissions (backpack)
        Permission::firstOrCreate(['name' => 'can_create_edit_client', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'can_create_client', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'can_delete_client', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'can_update_client', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'can_view_client', 'guard_name' => 'backpack']);

        // Supplier permissions (backpack)
        Permission::firstOrCreate(['name' => 'can_create_edit_supplier', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'can_create_supplier', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'can_delete_supplier', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'can_update_supplier', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'can_view_supplier', 'guard_name' => 'backpack']);

        // Invoice permissions (backpack)
        Permission::firstOrCreate(['name' => 'can_create_edit_invoice', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'can_create_invoice', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'can_delete_invoice', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'can_update_invoice', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'can_view_invoice', 'guard_name' => 'backpack']);

        // Product permissions (backpack)
        Permission::firstOrCreate(['name' => 'can_create_edit_product', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'can_create_product', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'can_delete_product', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'can_update_product', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'can_view_product', 'guard_name' => 'backpack']);

        // Expense permissions (backpack)
        Permission::firstOrCreate(['name' => 'can_create_edit_expense', 'guard_name' => 'backpack']);

        // Subscription permissions (backpack)
        Permission::firstOrCreate(['name' => 'can_create_edit_subscription', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'can_create_edit_subscription_plan', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'can_create_edit_subscription_plan_feature', 'guard_name' => 'backpack']);

        // Limit permissions (backpack)
        Permission::firstOrCreate(['name' => 'limit_daily_invoices_basic', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'limit_lifetime_products_basic', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'limit_monthly_invoices_basic', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'limit_weekly_invoices_basic', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'limit_yearly_clients_basic', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'limit_yearly_suppliers_basic', 'guard_name' => 'backpack']);

        // Frontend API permissions (web)
        Permission::firstOrCreate(['name' => 'frontend.api.access', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'frontend.api.clients', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'frontend.api.extended', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'frontend.api.invoices', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'frontend.api.products', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'frontend.api.statistics', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'frontend.api.suppliers', 'guard_name' => 'web']);

        // Frontend business permissions (web)
        Permission::firstOrCreate(['name' => 'frontend.can_create_edit_client', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'frontend.can_create_edit_expense', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'frontend.can_create_edit_invoice', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'frontend.can_create_edit_product', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'frontend.can_create_edit_status', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'frontend.can_create_edit_supplier', 'guard_name' => 'web']);

        // Frontend view permissions (web)
        Permission::firstOrCreate(['name' => 'frontend.can_view_client', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'frontend.can_view_invoice', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'frontend.can_view_product', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'frontend.can_view_status', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'frontend.can_view_supplier', 'guard_name' => 'web']);

        // Frontend delete permissions (web)
        Permission::firstOrCreate(['name' => 'frontend.can_delete_client', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'frontend.can_delete_invoice', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'frontend.can_delete_product', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'frontend.can_delete_status', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'frontend.can_delete_supplier', 'guard_name' => 'web']);

        // Frontend subscription permissions (web)
        Permission::firstOrCreate(['name' => 'frontend.can_cancel_subscription', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'frontend.can_create_subscription', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'frontend.can_view_subscription', 'guard_name' => 'web']);
    }

    /**
     * Create roles with proper permission assignments based on production data
     */
    private function createRoles(): void
    {
        // Admin role (backpack) - has all permissions
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'backpack']);
        $adminRole->syncPermissions(Permission::where('guard_name', 'backpack')->pluck('name'));

        // Backend user role (backpack) - limited access
        $backendUserRole = Role::firstOrCreate(['name' => 'backend_user', 'guard_name' => 'backpack']);
        $backendUserRole->syncPermissions([
            'backpack.access',
            'backpack.api.access',
            'backpack.api.clients',
            'backpack.api.invoices',
            'backpack.api.products',
            'backpack.api.suppliers',
            'can_view_client',
            'can_view_invoice',
            'can_view_product',
            'can_view_supplier',
            'can_view_user'
        ]);

        // Frontend user role (web) - basic frontend access
        $frontendUserRole = Role::firstOrCreate(['name' => 'frontend_user', 'guard_name' => 'web']);
        $frontendUserRole->syncPermissions([
            'frontend.api.access',
            'frontend.api.clients',
            'frontend.api.invoices',
            'frontend.api.products',
            'frontend.api.statistics',
            'frontend.api.suppliers',
            'frontend.can_create_edit_client',
            'frontend.can_create_edit_invoice',
            'frontend.can_create_edit_product',
            'frontend.can_create_edit_supplier',
            'frontend.can_view_client',
            'frontend.can_view_invoice',
            'frontend.can_view_product',
            'frontend.can_view_supplier'
        ]);

        // Frontend user plus role (web) - enhanced frontend access
        $frontendUserPlusRole = Role::firstOrCreate(['name' => 'frontend_user_plus', 'guard_name' => 'web']);
        $frontendUserPlusRole->syncPermissions([
            'frontend.api.access',
            'frontend.api.clients',
            'frontend.api.extended',
            'frontend.api.invoices',
            'frontend.api.products',
            'frontend.api.statistics',
            'frontend.api.suppliers',
            'frontend.can_cancel_subscription',
            'frontend.can_create_edit_client',
            'frontend.can_create_edit_expense',
            'frontend.can_create_edit_invoice',
            'frontend.can_create_edit_product',
            'frontend.can_create_edit_supplier',
            'frontend.can_create_subscription',
            'frontend.can_delete_client',
            'frontend.can_delete_invoice',
            'frontend.can_delete_product',
            'frontend.can_delete_supplier',
            'frontend.can_view_client',
            'frontend.can_view_invoice',
            'frontend.can_view_product',
            'frontend.can_view_subscription',
            'frontend.can_view_supplier'
        ]);
    }

    /**
     * Create test users with different roles (avoiding conflicts with production)
     */
    private function createTestUsers(): void
    {
        // Create admin test user (use different email to avoid conflict with production)
        $admin = User::firstOrCreate(
            ['email' => 'test-admin@example.com'],
            [
                'name' => 'Test Admin User',
                'password' => Hash::make('password'),
                'email_verified_at' => now()
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


        // Create backend user
        $backendUser = User::firstOrCreate(
            ['email' => 'test-backend@example.com'],
            [
                'name' => 'Test Backend User',
                'password' => Hash::make('password'),
                'email_verified_at' => now()
            ]
        );
        // Clear ALL existing roles and permissions for backend user first!
        $backendUser->roles()->detach();
        $backendUser->permissions()->detach();

        // Assign ONLY backpack admin role - no web permissions!
        $backendUserRole = Role::where('name', 'backend_user')->where('guard_name', 'backpack')->first();
        if ($backendUserRole) {
            $backendUser->assignRole($backendUserRole);
        }

        // Create frontend user
        $frontendUser = User::firstOrCreate(
            ['email' => 'test-frontend@example.com'],
            [
                'name' => 'Test Frontend User',
                'password' => Hash::make('password'),
                'email_verified_at' => now()
            ]
        );
        $frontendUser->assignRole('frontend_user');

        // Create frontend plus user
        $frontendPlusUser = User::firstOrCreate(
            ['email' => 'test-frontend-plus@example.com'],
            [
                'name' => 'Test Frontend Plus User',
                'password' => Hash::make('password'),
                'email_verified_at' => now()
            ]
        );
        $frontendPlusUser->assignRole('frontend_user_plus');
    }

    private function setupStatusCategories(): void
    {
        // Ensure invoice payment status category exists
        $category = StatusCategory::firstOrCreate([
            'slug' => 'invoice-payment'
        ], [
            'name' => 'Invoice Payment Status',
            'description' => 'Payment status for invoices'
        ]);

        // Seed all enum-defined system statuses (required ones)
        foreach (StatusCode::cases() as $case) {
            Status::firstOrCreate([
                'slug' => $case->value,
                'category_id' => $category->id
            ], [
                'name' => $case->label(),
                'description' => $case->label() . ' system status'
            ]);
        }
    }

    private function createArchivePolicies(): void
    {
        // Clear existing policies first
        DB::table('archive_policies')->delete();

        $policies = [
            ['table_name' => 'invoices', 'enabled' => true, 'retention_months' => 24, 'date_column' => 'created_at'],
            ['table_name' => 'clients', 'enabled' => true, 'retention_months' => 36, 'date_column' => 'created_at'],
            ['table_name' => 'suppliers', 'enabled' => true, 'retention_months' => 36, 'date_column' => 'created_at'],
        ];

        foreach ($policies as $policy) {
            DB::table('archive_policies')->updateOrInsert(
                ['table_name' => $policy['table_name']],
                array_merge($policy, [
                    'created_at' => now(),
                    'updated_at' => now()
                ])
            );
        }
    }

    private function createArtisanCommandCategory(): void
    {
        // Create a test category if needed
        ArtisanCommandCategory::firstOrCreate([
            'name' => 'General',
            'slug' => 'general'
        ]);
    }
}
