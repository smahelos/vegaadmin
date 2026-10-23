<?php

namespace Tests;

use App\Http\Middleware\SetLocale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Whether this process tests against in-memory SQLite (detected on the first test).
     */
    private static bool $usesInMemoryDatabase = false;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        // In-memory SQLite: give every test a fresh, freshly migrated database in both sequential
        // and parallel runs. Laravel otherwise reuses the PDO cached by a previous test and skips
        // migrations, so a test that disconnects or wipes it breaks every later test in the process.
        if (self::$usesInMemoryDatabase) {
            RefreshDatabaseState::$inMemoryConnections = [];
            RefreshDatabaseState::$migrated = false;
        }

        parent::setUp();

        // Built assets (public/build) are not versioned and CI does not run the Vite build
        $this->withoutVite();

        self::$usesInMemoryDatabase = $this->usesInMemorySqlite();

        // Persistent databases (sequential runs only): migrate and seed once per process
        if (! self::$usesInMemoryDatabase && ! $this->isParallelTestEnvironment()) {
            $this->setupSequentialTesting();
        }

        // Disable middleware that might cause issues during tests
        $this->withoutMiddleware(SetLocale::class);

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

    private function usesInMemorySqlite(): bool
    {
        $conn = config('database.connections.' . config('database.default'));

        return ($conn['driver'] ?? null) === 'sqlite' && in_array(
            $conn['database'] ?? null,
            [':memory:', null],
            true,
        );
    }

    /**
     * Setup for sequential testing against a persistent database - migrations run once
     */
    protected function setupSequentialTesting(): void
    {
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
            'backpack.access',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'backpack',
            ]);
        }
    }

    /**
     * Create admin user with all permissions for testing
     */
    protected function createAdminUserWithAllPermissions(): User
    {
        $this->createAdminMenuPermissions();

        $user = User::factory()->create();

        // Create admin role if it doesn't exist
        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'backpack',
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
     */
    protected function setupBackpackAdminTest(?User $user = null): User
    {
        $user = $user ?: User::factory()->create();

        // Create all menu permissions
        $this->createAdminMenuPermissions();

        // Create admin role if it doesn't exist
        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'backpack',
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
