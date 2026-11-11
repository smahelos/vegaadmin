<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use App\Domain\User\Enums\Permission;
use App\Infrastructure\Authorization\PermissionCatalog;
use App\Models\Permission as PermissionModel;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * A user can hold roles in both guards. A permission of one guard never passes a check of the other,
 * even when both guards store the same name.
 */
final class PermissionGuardsTest extends TestCase
{
    #[Test]
    public function frontend_permissions_do_not_open_admin_actions(): void
    {
        $userFactory = User::factory();
        $user = $userFactory->create();
        $user->assignRole(
            Role::findByName('frontend_user', PermissionCatalog::WEB),
        );
        // The admin admits only a user with a backpack role; this one grants no permission
        $user->assignRole(
            Role::findByName('backend_user', PermissionCatalog::BACKPACK),
        );
        $user->givePermissionTo(
            PermissionModel::findByName(Permission::BACKPACK_ACCESS->value, PermissionCatalog::BACKPACK),
        );
        $this->actingAs($user, 'backpack');

        $response = $this->get('/admin/product/create');
        $response->assertForbidden();
        $response = $this->post('/admin/product');
        $response->assertForbidden();
    }

    #[Test]
    public function admin_permissions_do_not_open_frontend_actions(): void
    {
        $userFactory = User::factory();
        $user = $userFactory->create();
        $user->assignRole(
            Role::findByName(PermissionCatalog::ADMIN_ROLE, PermissionCatalog::BACKPACK),
        );
        $this->actingAs($user, 'web');

        $response = $this->get('/cs/product/create');
        $response->assertForbidden();
        $response = $this->post('/cs/product');
        $response->assertForbidden();
    }
}
