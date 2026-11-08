<?php

declare(strict_types=1);

namespace Tests\Feature\Traits;

use App\Infrastructure\Authorization\Adapters\User\SpatieAuthorizationAdapter;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

final class SpatieAuthorizationAdapterBackpackFeatureTest extends TestCase
{
    private SpatieAuthorizationAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adapter = new SpatieAuthorizationAdapter;

        // Create required permissions/roles for backpack guard
        Permission::firstOrCreate(['name' => 'test.permission', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'another.permission', 'guard_name' => 'backpack']);
    }

    #[Test]
    public function has_backpack_permission_returns_false_without_permission(): void
    {
        $userFactory = User::factory();
        $user = $userFactory->create();
        $this->assertFalse(
            $this->adapter->hasBackpackPermission($user->id, 'test.permission'),
        );
    }

    #[Test]
    public function has_backpack_permission_returns_true_with_permission(): void
    {
        $userFactory = User::factory();
        $user = $userFactory->create();
        $permissionQuery = Permission::where('name', 'test.permission');
        $permissionQuery->where('guard_name', 'backpack');
        $permission = $permissionQuery->first();
        $user->givePermissionTo($permission);

        $this->assertTrue(
            $this->adapter->hasBackpackPermission($user->id, 'test.permission'),
        );
    }
}
