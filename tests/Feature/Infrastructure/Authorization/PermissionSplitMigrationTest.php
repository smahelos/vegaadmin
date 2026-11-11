<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure\Authorization;

use App\Domain\User\Enums\Permission;
use App\Models\Permission as PermissionModel;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\Support\RunsMigrationFile;
use Tests\TestCase;

final class PermissionSplitMigrationTest extends TestCase
{
    use RunsMigrationFile;

    private const MIGRATION = '2026_11_08_000001_finish_splitting_permissions_by_operation.php';

    /**
     * The migration speaks the names before the rename, so the rename is rolled back first.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->migrateDown(
            '2026_11_10_000001_rename_permissions_by_entity_and_action.php',
        );
    }

    #[Test]
    public function holders_of_an_old_permission_receive_its_successors(): void
    {
        $this->migrateDown(self::MIGRATION);
        $userFactory = User::factory();
        $holder = $userFactory->create();
        $holder->givePermissionTo(
            PermissionModel::findByName('can_create_edit_subscription', 'backpack'),
        );
        $role = Role::create(['name' => 'custom_editor', 'guard_name' => 'web']);
        $role->givePermissionTo('frontend.can_create_edit_product');

        $this->migrateUp(self::MIGRATION);

        $this->assertTrue(
            $holder->hasDirectPermission(
                PermissionModel::findByName('can_delete_subscription', 'backpack'),
            ),
        );
        $this->assertDatabaseMissing(
            'permissions',
            ['name' => 'can_create_edit_subscription', 'guard_name' => 'backpack'],
        );
        $role = Role::findByName('custom_editor', 'web');
        $this->assertTrue(
            $role->hasPermissionTo('frontend.can_update_product', 'web'),
        );
    }

    #[Test]
    public function permissions_nothing_checks_are_removed_and_frontend_users_may_delete_products(): void
    {
        $this->migrateDown(self::MIGRATION);

        $this->migrateUp(self::MIGRATION);

        $this->assertDatabaseMissing('permissions', ['name' => 'frontend.api.extended', 'guard_name' => 'web']);
        $this->assertDatabaseMissing(
            'permissions',
            ['name' => 'frontend.can_create_edit_expense', 'guard_name' => 'web'],
        );
        $role = Role::findByName('frontend_user', 'web');
        // The catalog sync grants it, and the sync writes the current names
        $this->assertTrue(
            $role->hasPermissionTo(Permission::PRODUCT_DELETE, 'web'),
        );
        $this->assertTrue(
            $role->hasPermissionTo('frontend.can_view_statistics', 'web'),
        );
    }

    #[Test]
    public function rollback_restores_the_old_permissions_with_their_role_grants(): void
    {
        $this->migrateDown(self::MIGRATION);

        $role = Role::findByName('admin', 'backpack');
        $this->assertTrue(
            $role->hasPermissionTo('can_create_edit_user', 'backpack'),
        );
        $role = Role::findByName('frontend_user', 'web');
        $this->assertTrue(
            $role->hasPermissionTo('frontend.api.statistics', 'web'),
        );
        $this->assertFalse(
            $role->hasPermissionTo('frontend.can_delete_product', 'web'),
        );
        $role = Role::findByName('frontend_user_plus', 'web');
        $this->assertTrue(
            $role->hasPermissionTo('frontend.can_create_edit_expense', 'web'),
        );
    }
}
