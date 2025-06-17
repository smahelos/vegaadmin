<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class ApiAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed basic permissions and roles for tests
        $this->createPermissions();
        $this->createRolesWithPermissions();
    }

    protected function createPermissions(): void
    {
        // Frontend permissions
        Permission::firstOrCreate(['name' => 'frontend.api.access', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'frontend.api.clients', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'frontend.api.extended', 'guard_name' => 'web']);
        
        // Backpack permissions
        Permission::firstOrCreate(['name' => 'backpack.api.access', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'backpack.access', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'can_view_client', 'guard_name' => 'backpack']);
    }

    protected function createRolesWithPermissions(): void
    {
        // Frontend roles
        $frontendRole = Role::firstOrCreate(['name' => 'frontend_user', 'guard_name' => 'web']);
        $frontendRole->syncPermissions([
            'frontend.api.access',
            'frontend.api.clients'
        ]);

        $frontendPlusRole = Role::firstOrCreate(['name' => 'frontend_user_plus', 'guard_name' => 'web']);
        $frontendPlusRole->syncPermissions([
            'frontend.api.access',
            'frontend.api.clients',
            'frontend.api.extended'
        ]);

        // Backpack roles
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'backpack']);
        $adminRole->syncPermissions([
            'backpack.api.access',
            'backpack.access',
            'can_view_client'
        ]);

        $backendUserRole = Role::firstOrCreate(['name' => 'backend_user', 'guard_name' => 'backpack']);
        $backendUserRole->syncPermissions([
            'backpack.access'
        ]);
    }

    #[Test]
    public function frontend_user_can_access_frontend_api()
    {
        // Create frontend user with role
        $user = User::factory()->create();
        $frontendRole = Role::where('name', 'frontend_user')->where('guard_name', 'web')->first();
        $user->assignRole($frontendRole);
        
        $this->actingAs($user, 'web');
        
        $this->assertTrue($user->hasPermissionTo('frontend.api.access', 'web'));
        $this->assertTrue($user->hasPermissionTo('frontend.api.clients', 'web'));
        $this->assertFalse($user->hasPermissionTo('frontend.api.extended', 'web'));
    }

    #[Test]
    public function admin_user_can_access_all_apis()
    {
        // Create admin user with role
        $user = User::factory()->create();
        $adminRole = Role::where('name', 'admin')->where('guard_name', 'backpack')->first();
        $user->assignRole($adminRole);
        
        $this->actingAs($user, 'backpack');
        
        $this->assertTrue($user->hasPermissionTo('backpack.api.access', 'backpack'));
        $this->assertTrue($user->hasPermissionTo('backpack.access', 'backpack'));
        $this->assertTrue($user->hasPermissionTo('can_view_client', 'backpack'));
    }

    #[Test]
    public function guard_separation_is_enforced()
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
    public function role_permissions_are_correctly_assigned()
    {
        $adminRole = Role::where('name', 'admin')->where('guard_name', 'backpack')->first();
        $backendUserRole = Role::where('name', 'backend_user')->where('guard_name', 'backpack')->first();
        $frontendRole = Role::where('name', 'frontend_user')->where('guard_name', 'web')->first();
        $frontendPlusRole = Role::where('name', 'frontend_user_plus')->where('guard_name', 'web')->first();

        // Admin should have backpack permissions
        $this->assertTrue($adminRole->hasPermissionTo('backpack.api.access', 'backpack'));
        $this->assertTrue($adminRole->hasPermissionTo('backpack.access', 'backpack'));

        // Backend user should have basic backpack access
        $this->assertTrue($backendUserRole->hasPermissionTo('backpack.access', 'backpack'));

        // Frontend user should have basic frontend permissions
        $this->assertTrue($frontendRole->hasPermissionTo('frontend.api.access', 'web'));
        $this->assertFalse($frontendRole->hasPermissionTo('frontend.api.extended', 'web'));

        // Frontend plus user should have extended permissions
        $this->assertTrue($frontendPlusRole->hasPermissionTo('frontend.api.extended', 'web'));
    }
}
