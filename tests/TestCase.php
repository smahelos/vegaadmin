<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Pro sekvenční testování spusť migrace pouze jednou
        // Pro paralelní testování se Laravel postará o databáze automaticky
        if (! $this->isParallelTestEnvironment()) {
            $this->setupSequentialTesting();
        }

        // Disable middleware that might cause issues during tests
        $this->withoutMiddleware(\App\Http\Middleware\SetLocale::class);

        // Load test routes
        if (app()->environment('testing')) {
            require_once __DIR__ . '/test_routes.php';
        }

        // Reset Spatie\Permission cached permissions to avoid memory bloat across long test runs
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Check if we're running in parallel test environment
     */
    protected function isParallelTestEnvironment(): bool
    {
        return isset($_ENV['TEST_TOKEN']) && $_ENV['TEST_TOKEN'];
    }

    /**
     * Setup for sequential testing - shared database with migrations run once
     */
    protected function setupSequentialTesting(): void
    {
        // Detect in-memory sqlite; let RefreshDatabase handle migrations per test
        $default = config('database.default');
        $conn = config("database.connections.$default");
        $isInMemorySqlite = ($conn['driver'] ?? null) === 'sqlite' && (($conn['database'] ?? null) === ':memory:' || ($conn['database'] ?? null) === null);

        if ($isInMemorySqlite) {
            // Ensure trait can run migrations; do not run artisan here (separate connection would discard schema)
            RefreshDatabaseState::$migrated = false;
            return;
        }

        // Use the original approach for persistent databases
        if (! RefreshDatabaseState::$migrated) {
            $this->artisan('migrate:fresh');
            $this->artisan('db:seed', ['--class' => 'DatabaseSeeder']);
            RefreshDatabaseState::$migrated = true;
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Sign in as a Backpack user
     *
     * @param User|null $user
     * @return $this
     */
    protected function actingAsBackpackUser(?User $user = null)
    {
        $user = $user ?: User::factory()->create();
        $guard = config('backpack.base.guard') ?: 'backpack';

        $this->actingAs($user, $guard);

        return $this;
    }

    /**
     * Create all required permissions used in admin menu to avoid dependency issues in tests
     *
     * @return void
     */
    protected function createAdminMenuPermissions(): void
    {
        $permissions = [
            // User management
            'can_create_edit_user',

            // Business operations
            'can_create_edit_invoice',
            'can_create_edit_client',
            'can_create_edit_supplier',

            // Financial management
            'can_create_edit_expense',
            'can_create_edit_tax',
            'can_create_edit_bank',
            'can_create_edit_payment_method',

            // Inventory management
            'can_create_edit_product',

            // System administration
            'can_create_edit_command',
            'can_create_edit_cron_task',
            'can_create_edit_status',

            // Database management
            'can_configure_system',

            // Page management
            'can_create_edit_page',
            'can_create_edit_page_category',

            // Additional basic permissions
            'backpack.access'
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'backpack'
            ]);
        }
    }

    /**
     * Create admin user with all permissions for testing
     *
     * @return User
     */
    protected function createAdminUserWithAllPermissions(): User
    {
        $this->createAdminMenuPermissions();

        $user = User::factory()->create();

        // Create admin role if it doesn't exist
        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'backpack'
        ]);

        // Give all permissions to admin role
        $permissions = Permission::where('guard_name', 'backpack')->get();
        $adminRole->syncPermissions($permissions);

        // Assign role to user
        $user->assignRole($adminRole);

        return $user;
    }

    /**
     * Quick setup for Backpack admin tests with all menu permissions
     *
     * @param User|null $user
     * @return User
     */
    protected function setupBackpackAdminTest(?User $user = null): User
    {
        $user = $user ?: User::factory()->create();

        // Create all menu permissions
        $this->createAdminMenuPermissions();

        // Create admin role if it doesn't exist
        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'backpack'
        ]);

        // Give all permissions to admin role
        $permissions = Permission::where('guard_name', 'backpack')->get();
        $adminRole->syncPermissions($permissions);

        // Assign role to user
        $user->assignRole($adminRole);

        // Authenticate user
        $this->actingAs($user, 'backpack');

        return $user;
    }
}
