<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure\Authorization;

use App\Domain\User\Enums\Permission;
use App\Models\Permission as PermissionModel;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class PermissionRenameMigrationTest extends TestCase
{
    #[Test]
    public function renaming_keeps_every_grant_and_every_revoked_default(): void
    {
        $role = Role::findByName('frontend_user', 'web');
        $role->revokePermissionTo(Permission::PRODUCT_DELETE);
        $userFactory = User::factory();
        $holder = $userFactory->create();
        $holder->givePermissionTo(
            PermissionModel::findByName(Permission::USER_VIEW->value, 'backpack'),
        );
        $permissionCount = PermissionModel::count();
        $migration = $this->migration();

        $migration->down();

        $this->assertDatabaseHas('permissions', ['name' => 'can_view_user', 'guard_name' => 'backpack']);
        $this->assertDatabaseHas('permissions', ['name' => 'frontend.can_view_product', 'guard_name' => 'web']);
        $this->assertDatabaseMissing('permissions', ['name' => Permission::PRODUCT_VIEW->value]);

        $migration->up();

        $this->assertSame($permissionCount, PermissionModel::count());
        $role = Role::findByName('frontend_user', 'web');
        $this->assertTrue(
            $role->hasPermissionTo(Permission::PRODUCT_VIEW, 'web'),
        );
        $this->assertFalse(
            $role->hasPermissionTo(Permission::PRODUCT_DELETE, 'web'),
        );
        $freshHolder = $holder->fresh();
        $this->assertTrue(
            $freshHolder->hasDirectPermission(
                PermissionModel::findByName(Permission::USER_VIEW->value, 'backpack'),
            ),
        );
    }

    private function migration(): Migration
    {
        return require database_path(
            'migrations/2026_11_10_000001_rename_permissions_by_entity_and_action.php',
        );
    }
}
