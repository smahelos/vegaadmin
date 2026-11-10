<?php

namespace Tests\Feature\Infrastructure\Authorization;

use App\Domain\User\Enums\Permission;
use App\Infrastructure\Authorization\PermissionCatalog;
use App\Infrastructure\Authorization\Services\PermissionCatalogSync;
use App\Models\Permission as PermissionModel;
use App\Models\Subscription;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PermissionCatalogSyncTest extends TestCase
{
    #[Test]
    public function migrations_create_the_catalog_permissions_and_roles(): void
    {
        foreach (PermissionCatalog::PERMISSIONS as $guard => $cases) {
            $names = array_column($cases, 'value');
            $permissionQuery = PermissionModel::where('guard_name', $guard);
            $permissionQuery->whereIn('name', $names);
            $permissionValues = $permissionQuery->pluck('name');
            $this->assertEqualsCanonicalizing(
                $names,
                $permissionValues->all(),
            );
        }

        $admin = Role::findByName(PermissionCatalog::ADMIN_ROLE, PermissionCatalog::BACKPACK);
        $permissionValues = $admin->permissions->pluck('name');
        $this->assertEqualsCanonicalizing(
            array_column(PermissionCatalog::PERMISSIONS[PermissionCatalog::BACKPACK], 'value'),
            $permissionValues->all(),
        );

        // Plan roles also hold limit_* permissions, which are plan configuration, not catalog entries
        foreach (PermissionCatalog::ROLES as $guard => $roles) {
            foreach ($roles as $role => $cases) {
                $names = array_column($cases, 'value');
                $catalogRole = Role::findByName($role, $guard);
                $permissionValues = $catalogRole->permissions->pluck('name');
                $catalogPermissions = $permissionValues->reject(
                    fn (string $name) => str_starts_with($name, 'limit_'),
                );
                $this->assertEqualsCanonicalizing(
                    $names,
                    $catalogPermissions->all(),
                    $role,
                );
            }
        }
    }

    #[Test]
    public function frontend_user_role_can_cancel_only_its_own_subscription(): void
    {
        $userFactory = User::factory();
        $user = $userFactory->create();
        $user->assignRole('frontend_user');

        $subscriptionFactory = Subscription::factory();
        $this->assertTrue(
            $user->can(
                'update',
                $subscriptionFactory->create(['user_id' => $user->id]),
            ),
        );
        $this->assertFalse(
            $user->can('update', $subscriptionFactory->create()),
        );
    }

    #[Test]
    public function sync_keeps_permissions_granted_through_permission_manager(): void
    {
        $custom = PermissionModel::create(
            ['name' => 'custom_' . uniqid(), 'guard_name' => PermissionCatalog::BACKPACK],
        );
        $backendUser = Role::findByName('backend_user', PermissionCatalog::BACKPACK);
        $backendUser->givePermissionTo([$custom, Permission::USER_VIEW]);
        $permissionCount = PermissionModel::count();

        $permissionCatalogSync = app(PermissionCatalogSync::class);
        $permissionCatalogSync->sync();

        $freshRole = $backendUser->fresh();
        $permissionValues = $freshRole->permissions->pluck('name');
        $this->assertEqualsCanonicalizing(
            [$custom->name, Permission::USER_VIEW->value],
            $permissionValues->all(),
        );
        $this->assertSame($permissionCount, PermissionModel::count());
    }
}
