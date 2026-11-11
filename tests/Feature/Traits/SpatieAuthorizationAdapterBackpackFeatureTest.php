<?php

declare(strict_types=1);

namespace Tests\Feature\Traits;

use App\Domain\User\Enums\Permission;
use App\Infrastructure\Authorization\Adapters\User\SpatieAuthorizationAdapter;
use App\Models\Permission as PermissionModel;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SpatieAuthorizationAdapterBackpackFeatureTest extends TestCase
{
    private SpatieAuthorizationAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adapter = new SpatieAuthorizationAdapter;
    }

    #[Test]
    public function has_backpack_permission_returns_false_without_permission(): void
    {
        $userFactory = User::factory();
        $user = $userFactory->create();
        $this->assertFalse(
            $this->adapter->hasBackpackPermission($user->id, Permission::USER_VIEW),
        );
    }

    #[Test]
    public function has_backpack_permission_returns_true_with_permission(): void
    {
        $userFactory = User::factory();
        $user = $userFactory->create();
        $user->givePermissionTo(
            PermissionModel::findByName(Permission::USER_VIEW->value, 'backpack'),
        );

        $this->assertTrue(
            $this->adapter->hasBackpackPermission($user->id, Permission::USER_VIEW),
        );
    }

    #[Test]
    public function the_same_permission_in_the_web_guard_does_not_count(): void
    {
        $userFactory = User::factory();
        $user = $userFactory->create();
        $user->givePermissionTo(
            PermissionModel::findByName(Permission::PRODUCT_VIEW->value, 'web'),
        );

        $this->assertFalse(
            $this->adapter->hasBackpackPermission($user->id, Permission::PRODUCT_VIEW),
        );
    }
}
