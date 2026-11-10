<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\User\Enums\Permission;
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
        $userFactory = User::factory();
        $user = $userFactory->create();
        $roleQuery = Role::where('name', 'frontend_user');
        $roleQuery->where('guard_name', 'web');
        $frontendRole = $roleQuery->first();
        $user->assignRole($frontendRole);

        $this->actingAs($user, 'web');

        $this->assertTrue(
            $user->hasPermissionTo(Permission::API_ACCESS, 'web'),
        );
        $this->assertTrue(
            $user->hasPermissionTo(Permission::CONTACT_VIEW, 'web'),
        );
    }

    #[Test]
    public function admin_user_can_access_all_apis(): void
    {
        // Create admin user with role
        $userFactory = User::factory();
        $user = $userFactory->create();
        $roleQuery = Role::where('name', 'admin');
        $roleQuery->where('guard_name', 'backpack');
        $adminRole = $roleQuery->first();
        $user->assignRole($adminRole);

        $this->actingAs($user, 'backpack');

        $this->assertTrue(
            $user->hasPermissionTo(Permission::API_ACCESS, 'backpack'),
        );
        $this->assertTrue(
            $user->hasPermissionTo(Permission::BACKPACK_ACCESS, 'backpack'),
        );
        $this->assertTrue(
            $user->hasPermissionTo(Permission::CONTACT_VIEW, 'backpack'),
        );
    }

    #[Test]
    public function guard_separation_is_enforced(): void
    {
        // Create users with different guards
        $userFactory = User::factory();
        $backpackUser = $userFactory->create();
        $roleQuery = Role::where('name', 'admin');
        $roleQuery->where('guard_name', 'backpack');
        $adminRole = $roleQuery->first();
        $backpackUser->assignRole($adminRole);

        $webUser = $userFactory->create();
        $roleQuery = Role::where('name', 'frontend_user');
        $roleQuery->where('guard_name', 'web');
        $frontendRole = $roleQuery->first();
        $webUser->assignRole($frontendRole);

        // Test cross-guard permissions are denied
        $this->assertFalse(
            $backpackUser->hasPermissionTo(Permission::API_ACCESS, 'web'),
        );
        $this->assertFalse(
            $webUser->hasPermissionTo(Permission::API_ACCESS, 'backpack'),
        );

        // Test same-guard permissions are allowed
        $this->assertTrue(
            $backpackUser->hasPermissionTo(Permission::API_ACCESS, 'backpack'),
        );
        $this->assertTrue(
            $webUser->hasPermissionTo(Permission::API_ACCESS, 'web'),
        );
    }

    #[Test]
    public function role_permissions_are_correctly_assigned(): void
    {
        $roleQuery = Role::where('name', 'admin');
        $roleQuery->where('guard_name', 'backpack');
        $adminRole = $roleQuery->first();
        $roleQuery = Role::where('name', 'backend_user');
        $roleQuery->where('guard_name', 'backpack');
        $backendUserRole = $roleQuery->first();
        $roleQuery = Role::where('name', 'frontend_user');
        $roleQuery->where('guard_name', 'web');
        $frontendRole = $roleQuery->first();

        // Admin should have backpack permissions
        $this->assertTrue(
            $adminRole->hasPermissionTo(Permission::API_ACCESS, 'backpack'),
        );
        $this->assertTrue(
            $adminRole->hasPermissionTo(Permission::BACKPACK_ACCESS, 'backpack'),
        );

        // Backend user should have basic backpack access
        $this->assertFalse(
            $backendUserRole->hasPermissionTo(Permission::BACKPACK_ACCESS, 'backpack'),
        );

        // Frontend user should have basic frontend permissions
        $this->assertTrue(
            $frontendRole->hasPermissionTo(Permission::API_ACCESS, 'web'),
        );
    }
}
