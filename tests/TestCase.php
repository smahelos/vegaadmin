<?php

declare(strict_types=1);

namespace Tests;

use App\Http\Middleware\SetLocale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use RefreshDatabase;

    /**
     * Migrate and seed DatabaseSeeder once per process; every test then runs in a transaction.
     */
    protected bool $seed = true;

    private bool $implicitCommitAllowed = false;

    protected function setUp(): void
    {
        parent::setUp();

        // Built assets (public/build) are not versioned and CI does not run the Vite build
        $this->withoutVite();

        // Disable middleware that might cause issues during tests
        $this->withoutMiddleware(SetLocale::class);

        // Reset Spatie\Permission cached permissions to avoid memory bloat across long test runs
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * RefreshDatabase migrates and seeds the database again before the next test when a test ends
     * outside its transaction (an implicit commit such as OPTIMIZE TABLE or TRUNCATE, a manual
     * rollback, a disconnect), which costs seconds per test.
     */
    protected function tearDown(): void
    {
        $leftTransaction = isset($this->app)
            && ! $this->app->make('db')->connection()->getPdo()->inTransaction();

        parent::tearDown();

        if ($leftTransaction && ! $this->implicitCommitAllowed) {
            $this->fail('The test ended outside the RefreshDatabase transaction, so the next test would migrate and seed again');
        }
    }

    /**
     * For a test of a statement MySQL commits implicitly; the next test migrates and seeds again.
     */
    protected function allowImplicitCommit(): void
    {
        $this->implicitCommitAllowed = true;
    }

    /**
     * RefreshDatabase wipes the database, so refuse anything but the test database — a cached
     * config or a missing phpunit.xml variable would otherwise point it at the development data.
     */
    protected function beforeRefreshingDatabase(): void
    {
        $database = (string) config('database.connections.' . config('database.default') . '.database');

        if (preg_match('/_testing(_test_\d+)?$/', $database) !== 1) {
            throw new RuntimeException('Tests run only against a *_testing database, not "' . $database . '"');
        }
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
