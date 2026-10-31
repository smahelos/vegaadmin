<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure\Authorization;

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class ObsoletePartyPermissionsTest extends TestCase
{
    #[Test]
    public function cleanup_removes_obsolete_grants_and_preserves_current_and_custom_permissions(): void
    {
        $migration = $this->migration();
        $migration->down();
        $owner = User::factory()->create();
        $owner->givePermissionTo(Permission::findByName('can_view_client', 'backpack'));
        $custom = Permission::create(['name' => 'custom_party_report', 'guard_name' => 'backpack']);
        Role::findByName('backend_user', 'backpack')->givePermissionTo($custom);

        $migration->up();
        $migration->up();

        $this->assertDatabaseMissing('permissions', ['name' => 'can_view_client', 'guard_name' => 'backpack']);
        $this->assertDatabaseMissing('permissions', ['name' => 'frontend.api.suppliers', 'guard_name' => 'web']);
        $this->assertSame([], $owner->fresh()->permissions->all());
        $this->assertTrue(Role::findByName('backend_user', 'backpack')->hasPermissionTo($custom));
        $this->assertTrue(Role::findByName('admin', 'backpack')->hasPermissionTo('can_view_contact', 'backpack'));
        $this->assertTrue(Role::findByName('frontend_user', 'web')->hasPermissionTo('frontend.can_create_business', 'web'));
    }

    #[Test]
    public function rollback_restores_obsolete_definitions_and_their_system_role_defaults(): void
    {
        $migration = $this->migration();
        $migration->down();
        $migration->down();

        $this->assertTrue(Role::findByName('admin', 'backpack')->hasPermissionTo('can_view_client', 'backpack'));
        $this->assertTrue(Role::findByName('frontend_user', 'web')->hasPermissionTo('frontend.api.clients', 'web'));
        $this->assertTrue(Role::findByName('frontend_user_plus', 'web')->hasPermissionTo('frontend.can_delete_supplier', 'web'));
        $migration->up();
        $this->assertDatabaseMissing('permissions', ['name' => 'frontend.api.clients', 'guard_name' => 'web']);
    }

    private function migration(): Migration
    {
        return require database_path('migrations/2026_10_30_000001_remove_obsolete_party_permissions.php');
    }
}
