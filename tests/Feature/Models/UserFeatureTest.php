<?php

namespace Tests\Feature\Models;

use App\Models\Client;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Feature tests for User Model
 *
 * Tests database relationships, business logic, and role-based functionality
 * Tests user interactions with clients, suppliers, and admin role functionality
 */
class UserFeatureTest extends TestCase
{
    use WithFaker;

    protected User $user;

    protected Role $adminRole;

    protected Role $userRole;

    /**
     * Set up the test environment.
     * Creates permissions, roles, and test users for model testing.
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
    public function user_has_many_clients_relationship(): void
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
    public function user_has_many_suppliers_relationship(): void
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
    public function user_can_have_both_clients_and_suppliers(): void
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
    public function user_deletion_behavior_with_related_models(): void
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

        // Users are soft deleted: the row stays, so clients and suppliers keep their owner
        $this->user->delete();

        $this->assertSoftDeleted('users', ['id' => $userId]);
        $this->assertSame($userId, Client::findOrFail($client->id)->user_id);
        $this->assertSame($userId, Supplier::findOrFail($supplier->id)->user_id);
    }

    #[Test]
    public function user_role_assignment_and_checking(): void
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
}
