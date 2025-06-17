<?php

namespace Tests\Feature\Models;

use App\Models\User;
use App\Models\Client;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Feature tests for User Model
 * 
 * Tests database relationships, business logic, and role-based functionality
 * Tests user interactions with clients, suppliers, and admin role functionality
 */
class UserTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Role $adminRole;
    protected Role $userRole;

    /**
     * Set up the test environment.
     * Creates permissions, roles, and test users for model testing.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions and roles
        $this->createPermissionsAndRoles();
        
        // Create test user
        $this->createTestUser();
    }

    /**
     * Create necessary permissions and roles for user model testing
     */
    private function createPermissionsAndRoles(): void
    {
        // Create basic permissions for both guards
        Permission::firstOrCreate(['name' => 'backpack.access', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'frontend.access', 'guard_name' => 'web']);
        
        // Create roles for testing
        $this->adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'backpack']);
        $this->userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        
        // Assign permissions to roles
        $this->adminRole->givePermissionTo('backpack.access');
        $this->userRole->givePermissionTo('frontend.access');
    }

    /**
     * Create test user with faker data
     */
    private function createTestUser(): void
    {
        $this->user = User::factory()->create([
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
        ]);
    }

    #[Test]
    public function user_has_many_clients_relationship()
    {
        // Initially no clients
        $this->assertInstanceOf(Collection::class, $this->user->clients);
        $this->assertEmpty($this->user->clients);
        
        // Create clients for the user
        $client1 = Client::factory()->create([
            'user_id' => $this->user->id,
            'name' => $this->faker->company,
            'email' => $this->faker->unique()->companyEmail,
        ]);
        
        $client2 = Client::factory()->create([
            'user_id' => $this->user->id,
            'name' => $this->faker->company,
            'email' => $this->faker->unique()->companyEmail,
        ]);
        
        // Refresh the user to load relationships
        $this->user->refresh();
        
        // Assert relationship works
        $this->assertCount(2, $this->user->clients()->get());
        $this->assertTrue($this->user->clients->contains($client1));
        $this->assertTrue($this->user->clients->contains($client2));
        
        // Assert all clients belong to this user
        foreach ($this->user->clients as $client) {
            $this->assertEquals($this->user->id, $client->user_id);
        }
    }

    #[Test]
    public function user_has_many_suppliers_relationship()
    {
        // Initially no suppliers
        $this->assertInstanceOf(Collection::class, $this->user->suppliers);
        $this->assertEmpty($this->user->suppliers);
        
        // Create suppliers for the user
        $supplier1 = Supplier::factory()->create([
            'user_id' => $this->user->id,
            'name' => $this->faker->company,
            'email' => $this->faker->unique()->companyEmail,
        ]);
        
        $supplier2 = Supplier::factory()->create([
            'user_id' => $this->user->id,
            'name' => $this->faker->company,
            'email' => $this->faker->unique()->companyEmail,
        ]);
        
        // Refresh the user to load relationships
        $this->user->refresh();
        
        // Assert relationship works
        $this->assertCount(2, $this->user->suppliers()->get());
        $this->assertTrue($this->user->suppliers->contains($supplier1));
        $this->assertTrue($this->user->suppliers->contains($supplier2));
        
        // Assert all suppliers belong to this user
        foreach ($this->user->suppliers as $supplier) {
            $this->assertEquals($this->user->id, $supplier->user_id);
        }
    }

    #[Test]
    public function is_admin_returns_true_with_admin_role()
    {
        // Assign admin role to user
        $this->user->assignRole($this->adminRole);
        
        // Refresh to ensure role is loaded
        $this->user->refresh();
        
        $this->assertTrue($this->user->is_admin());
    }

    #[Test]
    public function is_admin_returns_false_without_admin_role()
    {
        // Assign non-admin role to user
        $this->user->assignRole($this->userRole);
        
        // Refresh to ensure role is loaded
        $this->user->refresh();
        
        $this->assertFalse($this->user->is_admin());
    }

    #[Test]
    public function is_admin_returns_false_with_no_roles()
    {
        // User has no roles assigned
        $this->assertFalse($this->user->is_admin());
    }

    #[Test]
    public function user_can_have_both_clients_and_suppliers()
    {
        // Create both clients and suppliers
        $client = Client::factory()->create([
            'user_id' => $this->user->id,
            'name' => $this->faker->company,
            'email' => $this->faker->unique()->companyEmail,
        ]);
        
        $supplier = Supplier::factory()->create([
            'user_id' => $this->user->id,
            'name' => $this->faker->company,
            'email' => $this->faker->unique()->companyEmail,
        ]);
        
        // Refresh the user
        $this->user->refresh();
        
        // Assert both relationships work
        $this->assertCount(1, $this->user->clients()->get());
        $this->assertCount(1, $this->user->suppliers()->get());
        $this->assertTrue($this->user->clients->contains($client));
        $this->assertTrue($this->user->suppliers->contains($supplier));
    }

    #[Test]
    public function user_deletion_behavior_with_related_models()
    {
        // Create related models
        $client = Client::factory()->create([
            'user_id' => $this->user->id,
            'name' => $this->faker->company,
            'email' => $this->faker->unique()->companyEmail,
        ]);
        
        $supplier = Supplier::factory()->create([
            'user_id' => $this->user->id,
            'name' => $this->faker->company,
            'email' => $this->faker->unique()->companyEmail,
        ]);
        
        $userId = $this->user->id;
        
        // Delete the user
        $this->user->delete();
        
        // Check what happens to related models
        // This test documents the current behavior - adjust based on business rules
        $remainingClient = Client::find($client->id);
        $remainingSupplier = Supplier::find($supplier->id);
        
        // If cascade delete is NOT implemented, they should still exist but with null user_id
        // If cascade delete IS implemented, they should be null
        // Adjust assertions based on actual business requirements
        if ($remainingClient) {
            $this->assertNull($remainingClient->user_id);
        }
        
        if ($remainingSupplier) {
            $this->assertNull($remainingSupplier->user_id);
        }
    }

    #[Test]
    public function user_role_assignment_and_checking()
    {
        // Initially no roles
        $this->assertFalse($this->user->hasRole($this->adminRole));
        $this->assertFalse($this->user->hasRole($this->userRole));
        
        // Assign admin role
        $this->user->assignRole($this->adminRole);
        $this->user->refresh();
        
        $this->assertTrue($this->user->hasRole($this->adminRole));
        $this->assertFalse($this->user->hasRole($this->userRole));
        
        // Assign user role as well (user can have multiple roles)
        $this->user->assignRole($this->userRole);
        $this->user->refresh();
        
        $this->assertTrue($this->user->hasRole($this->adminRole));
        $this->assertTrue($this->user->hasRole($this->userRole));
        
        // Remove admin role
        $this->user->removeRole($this->adminRole);
        $this->user->refresh();
        
        $this->assertFalse($this->user->hasRole($this->adminRole));
        $this->assertTrue($this->user->hasRole($this->userRole));
    }

    #[Test]
    public function multiple_users_with_different_roles()
    {
        // Create another user
        $secondUser = User::factory()->create([
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
        ]);
        
        // Assign different roles
        $this->user->assignRole($this->adminRole);
        $secondUser->assignRole($this->userRole);
        
        // Refresh both users
        $this->user->refresh();
        $secondUser->refresh();
        
        // Assert different behaviors
        $this->assertTrue($this->user->is_admin());
        $this->assertFalse($secondUser->is_admin());
        
        $this->assertTrue($this->user->hasRole($this->adminRole));
        $this->assertTrue($secondUser->hasRole($this->userRole));
        
        $this->assertFalse($this->user->hasRole($this->userRole));
        $this->assertFalse($secondUser->hasRole($this->adminRole));
    }
}
