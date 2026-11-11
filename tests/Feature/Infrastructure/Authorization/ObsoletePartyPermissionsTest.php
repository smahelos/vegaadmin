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

final class ObsoletePartyPermissionsTest extends TestCase
{
    use RunsMigrationFile;

    private const MIGRATION = '2026_10_30_000001_remove_obsolete_party_permissions.php';

    #[Test]
    public function cleanup_removes_obsolete_grants_and_preserves_current_and_custom_permissions(): void
    {
        $this->migrateDown(self::MIGRATION);
        $userFactory = User::factory();
        $owner = $userFactory->create();
        $owner->givePermissionTo(
            PermissionModel::findByName('can_view_client', 'backpack'),
        );
        $custom = PermissionModel::create(
            ['name' => 'custom_party_report', 'guard_name' => 'backpack'],
        );
        $role = Role::findByName('backend_user', 'backpack');
        $role->givePermissionTo($custom);

        $this->migrateUp(self::MIGRATION);
        $this->migrateUp(self::MIGRATION);

        $this->assertDatabaseMissing('permissions', ['name' => 'can_view_client', 'guard_name' => 'backpack']);
        $this->assertDatabaseMissing('permissions', ['name' => 'frontend.api.suppliers', 'guard_name' => 'web']);
        $freshOwner = $owner->fresh();
        $this->assertSame([], $freshOwner->permissions->all());
        $role = Role::findByName('backend_user', 'backpack');
        $this->assertTrue(
            $role->hasPermissionTo($custom, 'backpack'),
        );
        $role = Role::findByName('admin', 'backpack');
        $this->assertTrue(
            $role->hasPermissionTo(Permission::CONTACT_VIEW, 'backpack'),
        );
        $role = Role::findByName('frontend_user', 'web');
        $this->assertTrue(
            $role->hasPermissionTo(Permission::BUSINESS_CREATE, 'web'),
        );
    }

    #[Test]
    public function rollback_restores_obsolete_definitions_and_their_system_role_defaults(): void
    {
        $this->migrateDown(self::MIGRATION);
        $this->migrateDown(self::MIGRATION);

        $role = Role::findByName('admin', 'backpack');
        $this->assertTrue(
            $role->hasPermissionTo('can_view_client', 'backpack'),
        );
        $role = Role::findByName('frontend_user', 'web');
        $this->assertTrue(
            $role->hasPermissionTo('frontend.api.clients', 'web'),
        );
        $role = Role::findByName('frontend_user_plus', 'web');
        $this->assertTrue(
            $role->hasPermissionTo('frontend.can_delete_supplier', 'web'),
        );
        $this->migrateUp(self::MIGRATION);
        $this->assertDatabaseMissing('permissions', ['name' => 'frontend.api.clients', 'guard_name' => 'web']);
    }
}
