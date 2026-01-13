<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

abstract class TestCaseParallel extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Track migration state per database for parallel testing
     */
    protected static array $migrationStates = [];

    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Get current database name for this test process
        $databaseName = $this->getDatabaseName();
        
        // For parallel testing, each process needs its own migration state
        if (! isset(static::$migrationStates[$databaseName])) {
            $this->setupDatabaseForParallelTesting($databaseName);
            static::$migrationStates[$databaseName] = true;
        }
        
        // Disable middleware that might cause issues during tests
        $this->withoutMiddleware(\App\Http\Middleware\SetLocale::class);
        
        // Load test routes
        if (app()->environment('testing')) {
            require_once __DIR__ . '/test_routes.php';
        }
    }

    /**
     * Get the database name for current test process
     */
    protected function getDatabaseName(): string
    {
        $databaseName = config('database.connections.testing.database');
        
        // For parallel testing, append process token to database name
        if (isset($_ENV['TEST_TOKEN']) && $_ENV['TEST_TOKEN']) {
            $databaseName .= '_' . $_ENV['TEST_TOKEN'];
        }
        
        return $databaseName;
    }

    /**
     * Setup database for parallel testing
     */
    protected function setupDatabaseForParallelTesting(string $databaseName): void
    {
        // Update the database configuration for this process
        config(['database.connections.testing.database' => $databaseName]);
        
        // Purge existing connection to ensure new config is used
        DB::purge('testing');
        
        // Ensure we're using the testing connection
        config(['database.default' => 'testing']);
        
        // Run migrations and seeds for this database
        Artisan::call('migrate:fresh', ['--database' => 'testing']);
        Artisan::call('db:seed', ['--database' => 'testing', '--class' => 'DatabaseSeeder']);
    }
    
    protected function tearDown(): void
    {
        parent::tearDown();
    }
    
    /**
     * Sign in as a Backpack user 
     *
     * @param User|null $user
     * @return User
     */
    protected function signInAsBackpackUser(User $user = null): User
    {
        $user = $user ?: User::factory()->create();
        $this->actingAs($user, 'backpack');
        return $user;
    }
    
    /**
     * Sign in as a regular frontend user
     *
     * @param User|null $user
     * @return User
     */
    protected function signInAsFrontendUser(User $user = null): User
    {
        $user = $user ?: User::factory()->create();
        $this->actingAs($user, 'web');
        return $user;
    }
    
    /**
     * Create a user with specific permission
     *
     * @param string $permission
     * @param string $guard
     * @return User
     */
    protected function createUserWithPermission(string $permission, string $guard = 'web'): User
    {
        $user = User::factory()->create();
        
        // Create permission if it doesn't exist
        Permission::firstOrCreate([
            'name' => $permission,
            'guard_name' => $guard
        ]);
        
        $user->givePermissionTo($permission);
        
        return $user;
    }
    
    /**
     * Create a user with specific role
     *
     * @param string $role
     * @param string $guard
     * @return User
     */
    protected function createUserWithRole(string $role, string $guard = 'web'): User
    {
        $user = User::factory()->create();
        
        // Create role if it doesn't exist
        $roleModel = Role::firstOrCreate([
            'name' => $role,
            'guard_name' => $guard
        ]);
        
        $user->assignRole($roleModel);
        
        return $user;
    }
    
    /**
     * Create an admin user with backpack access
     *
     * @return User
     */
    protected function createAdminUser(): User
    {
        $user = User::factory()->create();
        
        // Create admin role for backpack guard
        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'backpack'
        ]);
        
        // Create backpack access permission
        $backpackPermission = Permission::firstOrCreate([
            'name' => 'backpack.access',
            'guard_name' => 'backpack'
        ]);
        
        $adminRole->givePermissionTo($backpackPermission);
        $user->assignRole($adminRole);
        
        return $user;
    }
    
    /**
     * Create frontend user role and permissions for testing
     */
    protected function createBasicRolesAndPermissions(): void
    {
        // Create frontend user role
        $frontendRole = Role::firstOrCreate([
            'name' => 'frontend_user',
            'guard_name' => 'web'
        ]);
        
        // Create basic frontend permissions
        $permissions = [
            'can_create_edit_invoice',
            'can_create_edit_client',
            'can_create_edit_supplier',
            'can_create_edit_expense',
            'can_create_edit_product'
        ];
        
        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
        }
    }
}
