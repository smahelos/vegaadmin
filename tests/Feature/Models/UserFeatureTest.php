<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Business;
use App\Models\Contact;
use App\Models\Invoice;
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
 * Tests user interactions with contacts, businesses, and admin role functionality
 */
final class UserFeatureTest extends TestCase
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
    public function user_has_many_contacts_relationship(): void
    {
        // Initially no contacts
        $this->assertInstanceOf(Collection::class, $this->user->contacts);
        $this->assertEmpty($this->user->contacts);

        // Create contacts for the user
        $contact1 = Contact::factory()->create([
            'user_id' => $this->user->id,
            'name' => $this->faker->company,
            'email' => $this->faker->unique()->companyEmail,
        ]);

        $contact2 = Contact::factory()->create([
            'user_id' => $this->user->id,
            'name' => $this->faker->company,
            'email' => $this->faker->unique()->companyEmail,
        ]);

        // Refresh the user to load relationships
        $this->user->refresh();

        // Assert relationship works
        $this->assertCount(2, $this->user->contacts()->get());
        $this->assertTrue($this->user->contacts->contains($contact1));
        $this->assertTrue($this->user->contacts->contains($contact2));

        // Assert all contacts belong to this user
        foreach ($this->user->contacts as $contact) {
            $this->assertEquals($this->user->id, $contact->user_id);
        }
    }

    #[Test]
    public function user_has_many_businesses_relationship(): void
    {
        // Initially no businesses
        $this->assertInstanceOf(Collection::class, $this->user->businesses);
        $this->assertEmpty($this->user->businesses);

        // Create businesses for the user
        $business1 = Business::factory()->create([
            'user_id' => $this->user->id,
            'name' => $this->faker->company,
            'email' => $this->faker->unique()->companyEmail,
        ]);

        $business2 = Business::factory()->create([
            'user_id' => $this->user->id,
            'name' => $this->faker->company,
            'email' => $this->faker->unique()->companyEmail,
        ]);

        // Refresh the user to load relationships
        $this->user->refresh();

        // Assert relationship works
        $this->assertCount(2, $this->user->businesses()->get());
        $this->assertTrue($this->user->businesses->contains($business1));
        $this->assertTrue($this->user->businesses->contains($business2));

        // Assert all businesses belong to this user
        foreach ($this->user->businesses as $business) {
            $this->assertEquals($this->user->id, $business->user_id);
        }
    }

    #[Test]
    public function user_can_have_both_contacts_and_businesses(): void
    {
        // Create both contacts and businesses
        $contact = Contact::factory()->create([
            'user_id' => $this->user->id,
            'name' => $this->faker->company,
            'email' => $this->faker->unique()->companyEmail,
        ]);

        $business = Business::factory()->create([
            'user_id' => $this->user->id,
            'name' => $this->faker->company,
            'email' => $this->faker->unique()->companyEmail,
        ]);

        // Refresh the user
        $this->user->refresh();

        // Assert both relationships work
        $this->assertCount(1, $this->user->contacts()->get());
        $this->assertCount(1, $this->user->businesses()->get());
        $this->assertTrue($this->user->contacts->contains($contact));
        $this->assertTrue($this->user->businesses->contains($business));
    }

    #[Test]
    public function user_deletion_behavior_with_related_models(): void
    {
        // Create related models
        $contact = Contact::factory()->create([
            'user_id' => $this->user->id,
            'name' => $this->faker->company,
            'email' => $this->faker->unique()->companyEmail,
        ]);

        $business = Business::factory()->create([
            'user_id' => $this->user->id,
            'name' => $this->faker->company,
            'email' => $this->faker->unique()->companyEmail,
        ]);

        $userId = $this->user->id;

        // Users are soft deleted: the row stays, so contacts and businesses keep their owner
        $this->user->delete();

        $this->assertSoftDeleted('users', ['id' => $userId]);
        $this->assertSame($userId, Contact::findOrFail($contact->id)->user_id);
        $this->assertSame($userId, Business::findOrFail($business->id)->user_id);
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

    #[Test]
    public function invoices_belong_directly_to_the_user_including_one_off_customers(): void
    {
        $own = Invoice::factory()->create(['user_id' => $this->user->id, 'contact_id' => null]);
        Invoice::factory()->create();
        $this->assertSame([$own->id], $this->user->invoices()->pluck('id')->all());
    }
}
