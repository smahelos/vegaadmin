<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class ApiAuthenticationTest extends TestCase
{
    #[Test]
    public function frontend_user_can_access_frontend_api(): void
    {
        // Create frontend user with role
        $user = User::factory()->create();
        $frontendRole = Role::where('name', 'frontend_user')->where('guard_name', 'web')->first();
        $user->assignRole($frontendRole);

        $this->actingAs($user, 'web');

        $this->assertTrue($user->hasPermissionTo('frontend.api.access', 'web'));
        $this->assertTrue($user->hasPermissionTo('frontend.can_view_contact', 'web'));
        $this->assertFalse($user->hasPermissionTo('frontend.api.extended', 'web'));
    }

    #[Test]
    public function admin_user_can_access_all_apis(): void
    {
        // Create admin user with role
        $user = User::factory()->create();
        $adminRole = Role::where('name', 'admin')->where('guard_name', 'backpack')->first();
        $user->assignRole($adminRole);

        $this->actingAs($user, 'backpack');

        $this->assertTrue($user->hasPermissionTo('backpack.api.access', 'backpack'));
        $this->assertTrue($user->hasPermissionTo('backpack.access', 'backpack'));
        $this->assertTrue($user->hasPermissionTo('can_view_contact', 'backpack'));
    }

    #[Test]
    public function guard_separation_is_enforced(): void
    {
        // Create users with different guards
        $backpackUser = User::factory()->create();
        $adminRole = Role::where('name', 'admin')->where('guard_name', 'backpack')->first();
        $backpackUser->assignRole($adminRole);

        $webUser = User::factory()->create();
        $frontendRole = Role::where('name', 'frontend_user')->where('guard_name', 'web')->first();
        $webUser->assignRole($frontendRole);

        // Test cross-guard permissions are denied
        $this->assertFalse($backpackUser->hasPermissionTo('frontend.api.access', 'web'));
        $this->assertFalse($webUser->hasPermissionTo('backpack.api.access', 'backpack'));

        // Test same-guard permissions are allowed
        $this->assertTrue($backpackUser->hasPermissionTo('backpack.api.access', 'backpack'));
        $this->assertTrue($webUser->hasPermissionTo('frontend.api.access', 'web'));
    }

    #[Test]
    public function role_permissions_are_correctly_assigned(): void
    {
        $adminRole = Role::where('name', 'admin')->where('guard_name', 'backpack')->first();
        $backendUserRole = Role::where('name', 'backend_user')->where('guard_name', 'backpack')->first();
        $frontendRole = Role::where('name', 'frontend_user')->where('guard_name', 'web')->first();
        $frontendPlusRole = Role::where('name', 'frontend_user_plus')->where('guard_name', 'web')->first();

        // Admin should have backpack permissions
        $this->assertTrue($adminRole->hasPermissionTo('backpack.api.access', 'backpack'));
        $this->assertTrue($adminRole->hasPermissionTo('backpack.access', 'backpack'));

        // Backend user should have basic backpack access
        $this->assertFalse($backendUserRole->hasPermissionTo('backpack.access', 'backpack'));

        // Frontend user should have basic frontend permissions
        $this->assertTrue($frontendRole->hasPermissionTo('frontend.api.access', 'web'));
        $this->assertFalse($frontendRole->hasPermissionTo('frontend.api.extended', 'web'));

        // Frontend plus user should have extended permissions
        $this->assertTrue($frontendPlusRole->hasPermissionTo('frontend.api.extended', 'web'));
    }
}
