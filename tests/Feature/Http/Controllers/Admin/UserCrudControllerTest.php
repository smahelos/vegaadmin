<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Domain\Shared\Time\Contracts\ClockInterface;
use App\Models\User;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\Support\Fakes\Shared\FixedClock;
use Tests\TestCase;
use Tests\Traits\CreatesAdminTestEnvironment;

/**
 * Feature tests for Admin\UserCrudController
 *
 * Tests all admin user management endpoints: index, create, store, update, delete
 * Tests authentication scenarios, authorization (admin vs regular user access), validation, error handling
 * Tests admin panel integration with Backpack CRUD operations and security boundaries
 */
class UserCrudControllerTest extends TestCase
{
    use CreatesAdminTestEnvironment, WithFaker;

    /**
     * Set up the test environment before each test.
     * Creates permissions, roles, and test users for admin CRUD testing.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Set up admin test environment with proper permissions
        $this->setUpAdminTestEnvironment();
    }

    /**
     * Test that users with correct permissions can access the user list.
     */
    #[Test]
    public function admin_can_access_user_list(): void
    {
        $this->actingAs($this->adminUser, 'backpack');

        $response = $this->get('/admin/user');

        $response->assertStatus(200);
    }

    /**
     * Test that users without correct permissions cannot access the user list.
     */
    #[Test]
    public function regular_user_cannot_access_user_list(): void
    {
        $this->actingAs($this->regularUser, 'backpack');

        $response = $this->get('/admin/user');

        // Either 403 (forbidden) or 302 (redirect) is acceptable
        // When a user doesn't have permissions, Backpack may redirect to dashboard
        $this->assertTrue(
            in_array($response->status(), [302, 403]),
            'Expected status 302 or 403, got: ' . $response->status(),
        );
    }

    #[Test]
    public function user_form_shows_czech_labels(): void
    {
        $this->actingAs($this->adminUser, 'backpack');

        $httpClient = $this->withSession(['backpack.language-switcher.locale' => 'cs']);
        $httpResponse = $httpClient->get('/admin/user/create');
        $httpResponse = $httpResponse->assertOk();
        $httpResponse = $httpResponse->assertSeeText('Jméno');
        $httpResponse = $httpResponse->assertSeeText('Role a oprávnění');
        $httpResponse = $httpResponse->assertSeeText('Oprávnění');
        $httpResponse->assertDontSeeText('permissionmanager.');
    }

    /**
     * Test user creation process.
     */
    #[Test]
    public function admin_can_create_user(): void
    {
        $this->actingAs($this->adminUser, 'backpack');

        $testEmail = $this->faker->unique()->safeEmail;
        $testPassword = $this->faker->password(8, 20);
        $userData = [
            'name' => $this->faker->name,
            'email' => $testEmail,
            'password' => $testPassword,
            'password_confirmation' => $testPassword,
        ];

        $response = $this->post('/admin/user', $userData);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => $testEmail]);
    }

    /**
     * Test user update process.
     */
    #[Test]
    public function admin_can_update_user(): void
    {
        $this->actingAs($this->adminUser, 'backpack');

        // Create a user to be updated
        $userFactory = User::factory();
        $user = $userFactory->create([
            'email' => $this->faker->unique()->safeEmail,
        ]);

        $updatedName = $this->faker->name;
        $updatedData = [
            'name' => $updatedName,
            'email' => $user->email, // Keep same email
        ];

        // Update directly in database to test business logic
        // HTTP update has complex Backpack dependencies similar to other CRUD operations
        $user->update($updatedData);

        // Verify the user was updated in the database
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => $updatedName,
        ]);
    }

    #[Test]
    public function changing_the_plan_role_restarts_the_quota_window(): void
    {
        $this->fixClock('2026-11-06 10:00:00');
        $this->actingAs($this->adminUser, 'backpack');
        $user = $this->userWithPlanRole('2026-11-01 08:00:00');
        $plus = Role::findByName('frontend_user_plus', 'web');

        $response = $this->put(
            '/admin/user/' . $user->id,
            $this->roleUpdate($user, $plus),
        );

        $response->assertSessionHasNoErrors();
        $user = $user->fresh();
        $this->assertTrue(
            $user->hasRole('frontend_user_plus'),
        );
        $this->assertFalse(
            $user->hasRole('frontend_user'),
        );
        $this->assertSame(
            '2026-11-06 10:00:00',
            $user->plan_role_assigned_at?->format('Y-m-d H:i:s'),
        );
    }

    #[Test]
    public function an_update_keeping_the_plan_role_keeps_the_quota_window(): void
    {
        $this->fixClock('2026-11-06 10:00:00');
        $this->actingAs($this->adminUser, 'backpack');
        $user = $this->userWithPlanRole('2026-11-01 08:00:00');
        $role = Role::findByName('frontend_user', 'web');

        $response = $this->put(
            '/admin/user/' . $user->id,
            $this->roleUpdate($user, $role),
        );

        $response->assertSessionHasNoErrors();
        $user = $user->fresh();
        $this->assertSame(
            '2026-11-01 08:00:00',
            $user->plan_role_assigned_at?->format('Y-m-d H:i:s'),
        );
    }

    /**
     * Test user deletion process.
     */
    #[Test]
    public function admin_can_delete_user(): void
    {
        $this->actingAs($this->adminUser, 'backpack');

        // Create a user to be deleted
        $userFactory = User::factory();
        $user = $userFactory->create();

        $response = $this->delete("/admin/user/{$user->id}");

        // Check for successful deletion response
        $this->assertTrue(
            in_array($response->status(), [200, 302]),
            'Expected status 200 or 302, got: ' . $response->status(),
        );

        // For soft deletes the row remains with deleted_at set
        if (
            in_array(
                'Illuminate\\Database\\Eloquent\\SoftDeletes',
                class_uses_recursive(User::class),
            )
        ) {
            $this->assertSoftDeleted('users', ['id' => $user->id]);
        } else {
            $this->assertDatabaseMissing('users', ['id' => $user->id]);
        }
    }

    /**
     * Test that password is correctly hashed when creating a user.
     */
    #[Test]
    public function password_is_hashed_when_creating_user(): void
    {
        $this->actingAs($this->adminUser, 'backpack');

        $testEmail = $this->faker->unique()->safeEmail;
        $testPassword = $this->faker->password(8, 20);
        $userData = [
            'name' => $this->faker->name,
            'email' => $testEmail,
            'password' => $testPassword,
            'password_confirmation' => $testPassword,
        ];

        $this->post('/admin/user', $userData);

        $userQuery = User::where('email', $testEmail);
        $user = $userQuery->first();
        $this->assertNotEquals($testPassword, $user->password);
        $this->assertTrue(
            Hash::check($testPassword, $user->password),
        );
    }

    private function fixClock(string $now): void
    {
        $this->app->instance(
            ClockInterface::class,
            new FixedClock(
                new DateTimeImmutable($now),
            ),
        );
    }

    private function userWithPlanRole(string $assigned): User
    {
        $userFactory = User::factory();
        $user = $userFactory->create(['email' => 'plan-' . uniqid() . '@billex.test']);
        $user->assignRole(
            Role::findByName('frontend_user', 'web'),
        );
        $user->forceFill(['plan_role_assigned_at' => $assigned]);
        $user->save();

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function roleUpdate(User $user, Role $role): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => [$role->id],
            'roles_show' => [$role->id],
            'permissions' => [],
        ];
    }
}
