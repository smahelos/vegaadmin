<?php

namespace Tests\Feature\Infrastructure\Authorization;

use App\Infrastructure\Authorization\PermissionCatalog;
use App\Infrastructure\Authorization\Services\PermissionCatalogSync;
use App\Models\Subscription;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PermissionCatalogSyncTest extends TestCase
{
    #[Test]
    public function migrations_create_the_catalog_permissions_and_roles(): void
    {
        foreach (PermissionCatalog::PERMISSIONS as $guard => $names) {
            $this->assertEqualsCanonicalizing($names, Permission::where('guard_name', $guard)->whereIn('name', $names)->pluck('name')->all());
        }

        $admin = Role::findByName(PermissionCatalog::ADMIN_ROLE, PermissionCatalog::BACKPACK);
        $this->assertEqualsCanonicalizing(PermissionCatalog::PERMISSIONS[PermissionCatalog::BACKPACK], $admin->permissions->pluck('name')->all());

        // Plan roles also hold limit_* permissions, which are plan configuration, not catalog entries
        foreach (PermissionCatalog::ROLES as $guard => $roles) {
            foreach ($roles as $role => $names) {
                $this->assertEqualsCanonicalizing(
                    $names,
                    Role::findByName($role, $guard)->permissions->pluck('name')
                        ->reject(fn (string $name) => str_starts_with($name, 'limit_'))
                        ->all(),
                    $role,
                );
            }
        }
    }

    #[Test]
    public function frontend_user_role_can_cancel_only_its_own_subscription(): void
    {
        $user = User::factory()->create();
        $user->assignRole('frontend_user');

        $this->assertTrue($user->can('update', Subscription::factory()->create(['user_id' => $user->id])));
        $this->assertFalse($user->can('update', Subscription::factory()->create()));
    }

    #[Test]
    public function sync_keeps_permissions_granted_through_permission_manager(): void
    {
        $custom = Permission::create(['name' => 'custom_' . uniqid(), 'guard_name' => PermissionCatalog::BACKPACK]);
        $backendUser = Role::findByName('backend_user', PermissionCatalog::BACKPACK);
        $backendUser->givePermissionTo([$custom, 'can_view_user']);
        $permissionCount = Permission::count();

        app(PermissionCatalogSync::class)->sync();

        $this->assertEqualsCanonicalizing([$custom->name, 'can_view_user'], $backendUser->fresh()->permissions->pluck('name')->all());
        $this->assertSame($permissionCount, Permission::count());
    }
}
