<?php

namespace Tests\Feature\Traits;

use App\Infrastructure\Authorization\Adapters\User\SpatieAuthorizationAdapter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SpatieAuthorizationAdapterBackpackFeatureTest extends TestCase
{
    use RefreshDatabase;

    private SpatieAuthorizationAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adapter = new SpatieAuthorizationAdapter();

        // Create required permissions/roles for backpack guard
        Permission::firstOrCreate(['name' => 'can_view_client', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'can_create_edit_client', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'test.permission', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'another.permission', 'guard_name' => 'backpack']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'backpack']);
    }

    #[Test]
    public function has_backpack_permission_returns_false_without_permission(): void
    {
        $user = User::factory()->create();
        $this->assertFalse($this->adapter->hasBackpackPermission($user->id, 'test.permission'));
    }

    #[Test]
    public function has_backpack_permission_returns_true_with_permission(): void
    {
        $user = User::factory()->create();
        $permission = Permission::where('name', 'test.permission')->where('guard_name', 'backpack')->first();
        $user->givePermissionTo($permission);

        $this->assertTrue($this->adapter->hasBackpackPermission($user->id, 'test.permission'));
    }

    #[Test]
    public function is_backpack_admin_true_when_role_assigned(): void
    {
        $user = User::factory()->create();
        $role = Role::where('name', 'admin')->where('guard_name', 'backpack')->first();
        $user->assignRole($role);

        $this->assertTrue($this->adapter->isBackpackAdmin($user->id));
    }

    #[Test]
    public function has_backpack_view_permission_true_with_permission(): void
    {
        $user = User::factory()->create();
        $viewPermission = Permission::where('name', 'can_view_client')->where('guard_name', 'backpack')->first();
        $user->givePermissionTo($viewPermission);

        $this->assertTrue($this->adapter->hasBackpackViewPermission($user->id, 'client'));
        $this->assertFalse($this->adapter->hasBackpackViewPermission($user->id, 'supplier'));
    }

    #[Test]
    public function can_access_any_clients_works_for_admin_or_view_permission(): void
    {
        $admin = User::factory()->create();
        $role = Role::where('name', 'admin')->where('guard_name', 'backpack')->first();
        $admin->assignRole($role);
        $this->assertTrue($this->adapter->canAccessAnyClients($admin->id));

        $viewer = User::factory()->create();
        $viewer->givePermissionTo(
            Permission::where('name', 'can_view_client')->where('guard_name', 'backpack')->first()
        );
        $this->assertTrue($this->adapter->canAccessAnyClients($viewer->id));

        $plain = User::factory()->create();
        $this->assertFalse($this->adapter->canAccessAnyClients($plain->id));
    }
}
