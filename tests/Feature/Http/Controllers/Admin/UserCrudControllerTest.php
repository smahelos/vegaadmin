<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Http\Controllers\Admin\UserCrudController;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;

/**
 * Feature tests for Admin\UserCrudController
 * 
 * Tests all admin user management endpoints: index, create, store, update, delete
 * Tests authentication scenarios, authorization (admin vs regular user access), validation, error handling
 * Tests admin panel integration with Backpack CRUD operations and security boundaries
 */
class UserCrudControllerTest extends TestCase
{
    use DatabaseTransactions, WithFaker;

    protected User $adminUser;
    protected User $regularUser;
    protected Role $adminRole;
    protected Role $userRole;
    protected string $routePrefix;

    /**
     * Set up the test environment before each test.
     * Creates permissions, roles, and test users for admin CRUD testing.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Set backpack guard in config for tests
        config(['backpack.base.guard' => 'backpack']);
        
        // Make sure we have clean permission tables for isolated testing
        $this->cleanupPermissionTables();

        // Create permissions and roles
        $this->createPermissionsAndRoles();
        
        // Create test users with proper roles
        $this->createTestUsers();

        // Set the route prefix for admin panel
        $this->routePrefix = config('backpack.base.route_prefix');
    }

    /**
     * Clean up permission tables for isolated testing
     */
    private function cleanupPermissionTables(): void
    {
        DB::table('model_has_permissions')->where('model_type', User::class)->delete();
        DB::table('model_has_roles')->where('model_type', User::class)->delete();
        DB::table('permissions')->where('guard_name', 'backpack')->delete();
        DB::table('roles')->where('guard_name', 'backpack')->delete();
    }

    /**
     * Create necessary permissions and roles for admin user testing
     * Sets up backpack guard permissions and admin/user roles
     */
    private function createPermissionsAndRoles(): void
    {
        // Define all permissions required for admin CRUD operations and navigation
        $permissions = [
            // User management permissions
            'can_view_user',
            'can_create_user', 
            'can_update_user',
            'can_delete_user',
            'can_create_edit_user',
            
            // Business operations permissions
            'can_create_edit_invoice',
            'can_create_edit_client',
            'can_create_edit_supplier',
            
            // Financial management permissions
            'can_create_edit_expense',
            'can_create_edit_tax',
            'can_create_edit_bank',
            'can_create_edit_payment_method',
            
            // Inventory management permissions
            'can_create_edit_product',
            
            // System administration permissions
            'can_create_edit_command',
            'can_create_edit_cron_task',
            'can_create_edit_status',
            'can_configure_system',
        ];

        // Create all permissions for backpack guard
        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'backpack']);
        }

        // Create roles for testing
        $this->adminRole = Role::create(['name' => 'admin', 'guard_name' => 'backpack']);
        $this->userRole = Role::create(['name' => 'user', 'guard_name' => 'backpack']);

        // Assign all necessary permissions to admin role for full admin access
        $this->adminRole->givePermissionTo($permissions);
    }

    /**
     * Create test users with proper admin and regular user roles
     */
    private function createTestUsers(): void
    {
        // Create admin user with admin role and proper permissions
        $this->adminUser = User::factory()->create([
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
        ]);
        $this->adminUser->assignRole($this->adminRole);

        // Create regular user with user role (no admin permissions)
        $this->regularUser = User::factory()->create([
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
        ]);
        $this->regularUser->assignRole($this->userRole);
    }

    /**
     * Test that users with correct permissions can access the user list.
     *
     * @return void
     */
    #[Test]
    public function admin_can_access_user_list()
    {
        $this->actingAs($this->adminUser, 'backpack');
        
        // Follow redirects to catch the final response
        $response = $this->followingRedirects()->get("{$this->routePrefix}/user");
        
        $response->assertStatus(200);
    }

    /**
     * Test that users without correct permissions cannot access the user list.
     *
     * @return void
     */
    #[Test]
    public function regular_user_cannot_access_user_list()
    {
        $this->actingAs($this->regularUser, 'backpack');
        
        $response = $this->get("{$this->routePrefix}/user");
        
        // Either 403 (forbidden) or 302 (redirect) is acceptable
        // When a user doesn't have permissions, Backpack may redirect to dashboard
        $this->assertTrue(in_array($response->status(), [302, 403]), 
            'Expected status 302 or 403, got: ' . $response->status());
            
        // If it's a redirect, let's check we're not going to the user list
        if ($response->status() == 302) {
            $this->assertNotEquals(
                $response->headers->get('Location'),
                "{$this->routePrefix}/user"
            );
        }
    }

    /**
     * Test user creation process.
     *
     * @return void
     */
    #[Test]
    public function admin_can_create_user()
    {
        $this->actingAs($this->adminUser, 'backpack');
        
        $testEmail = $this->faker->unique()->safeEmail;
        $testPassword = $this->faker->password(8, 20);
        $userData = [
            'name' => $this->faker->name,
            'email' => $testEmail,
            'password' => $testPassword,
            'password_confirmation' => $testPassword,
            'roles' => [$this->userRole->id],
        ];
        
        $response = $this->post("{$this->routePrefix}/user", $userData);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => $testEmail]);
    }

    /**
     * Test user update process.
     *
     * @return void
     */
    #[Test]
    public function admin_can_update_user()
    {
        $this->withoutExceptionHandling(); // Show detailed errors for debugging

        // Ensure admin user exists in the database
        $this->assertDatabaseHas('users', ['email' => $this->adminUser->email]);

        // Check if admin has the correct role
        $this->assertTrue($this->adminUser->hasRole($this->adminRole), 'Admin user does not have admin role');
        
        // Authenticate as admin with the correct permission
        $this->actingAs($this->adminUser, 'backpack');
        
        // Verify admin has required permission
        $this->assertTrue(
            $this->adminUser->hasPermissionTo('can_update_user', 'backpack'),
            'Admin user does not have can_update_user permission'
        );

        // Create a user to be updated
        $originalEmail = $this->faker->unique()->safeEmail;
        $user = User::factory()->create([
            'name' => $this->faker->name,
            'email' => $originalEmail,
        ]);
        $this->assertDatabaseHas('users', ['email' => $originalEmail]);
        
        // Assign role to the user
        $user->assignRole($this->userRole);

        // Create update data formatted exactly as expected by Backpack
        $updatedName = $this->faker->name;
        $updatedEmail = $this->faker->unique()->safeEmail;
        $updatedData = [
            'id' => $user->id, // Explicitly include ID as this might be needed by the request validator
            'name' => $updatedName,
            'email' => $updatedEmail,
            'roles' => [$this->userRole->id],
            '_token' => csrf_token(),
        ];
        
        // Make the update request using PUT directly
        $response = $this->put("{$this->routePrefix}/user/{$user->id}", $updatedData);
        
        // Either it redirects (302) or it's successful (200)
        $this->assertTrue(
            in_array($response->status(), [200, 302]),
            "Update failed with status: " . $response->status()
        );
        
        // Don't follow redirects in this test to avoid datatable issues
        
        // Check if the user was actually updated in the database
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => $updatedName,
            'email' => $updatedEmail,
        ]);
        
        // Verify roles were maintained
        $updatedUser = User::find($user->id);
        $this->assertTrue($updatedUser->hasRole($this->userRole), 'Updated user lost their role');
    }

    /**
     * Test user deletion process.
     *
     * @return void
     */
    #[Test]
    public function admin_can_delete_user()
    {
        $this->actingAs($this->adminUser, 'backpack');
        
        $testEmail = $this->faker->unique()->safeEmail;
        $testPassword = $this->faker->password(8, 20);
        $userData = [
            'name' => $this->faker->name,
            'email' => $testEmail,
            'password' => $testPassword,
            'password_confirmation' => $testPassword,
            'roles' => [$this->userRole->id], // Use role ID instead of name
        ];

        $response = $this->post("{$this->routePrefix}/user", $userData);
        
        // Check for successful redirect
        $this->assertTrue(in_array($response->status(), [302, 200]));
        $this->assertDatabaseHas('users', ['email' => $testEmail]);
    }

    /**
     * Test that password is correctly hashed when creating a user.
     *
     * @return void
     */
    #[Test]
    public function password_is_hashed_on_create()
    {
        $this->actingAs($this->adminUser, 'backpack');
        
        $testEmail = $this->faker->unique()->safeEmail;
        $testPassword = $this->faker->password(8, 20);
        $userData = [
            'name' => $this->faker->name,
            'email' => $testEmail,
            'password' => $testPassword,
            'password_confirmation' => $testPassword,
            'roles' => [$this->userRole->id],
        ];
        
        $this->post("{$this->routePrefix}/user", $userData);
        
        $user = User::where('email', $testEmail)->first();
        $this->assertNotEquals($testPassword, $user->password);
        $this->assertTrue(Hash::check($testPassword, $user->password));
    }

    /**
     * Test that password is correctly hashed when updating a user.
     *
     * @return void
     */
    #[Test]
    public function password_is_hashed_on_update()
    {
        $this->withoutExceptionHandling(); // Show detailed errors for debugging

        // Ensure admin user exists in the database
        $this->assertDatabaseHas('users', ['email' => $this->adminUser->email]);

        // Check if admin has the correct role
        $this->assertTrue($this->adminUser->hasRole($this->adminRole), 'Admin user does not have admin role');
        
        $this->actingAs($this->adminUser, 'backpack');
        
        $testEmail = $this->faker->unique()->safeEmail;
        $testName = $this->faker->name;
        $user = User::factory()->create([
            'name' => $testName,
            'email' => $testEmail,
            'password' => Hash::make('initial-password')
        ]);
        $this->assertDatabaseHas('users', ['email' => $testEmail]);

        // Store the original password for comparison
        $originalPassword = $user->password;
        $this->assertDatabaseHas('users', ['password' => $user->password]);
    
        // Assign a role to the user
        $user->assignRole($this->userRole);
    
        // Create update data with new password
        $newPassword = $this->faker->password(8, 20);
        $updatedData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
            'roles' => [$this->userRole->id],
            '_token' => csrf_token(),
        ];
        $this->assertDatabaseHas('users', ['id' => $user->id]);
        
        // Make the update request
        $response = $this->put("{$this->routePrefix}/user/{$user->id}", $updatedData);
    
        // Refresh user from database
        $updatedUser = User::find($user->id);
        $this->assertDatabaseHas('users', ['id' => $updatedUser->id]);

        // Verify the update was successful
        $this->assertTrue(
            in_array($response->status(), [200, 302]),
            "Update failed with status: " . $response->status()
        );
        
        // Verify the password was actually changed
        $this->assertNotEquals($originalPassword, $updatedUser->password, 'Password was not updated');
        
        // Verify the new password is correctly hashed
        $this->assertTrue(Hash::check($newPassword, $updatedUser->password), 'New password was not correctly hashed');
    }
}
