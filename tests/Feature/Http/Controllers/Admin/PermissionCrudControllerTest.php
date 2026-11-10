<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin;

use App\Domain\User\Enums\Permission;
use App\Models\Permission as PermissionModel;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesAdminTestEnvironment;

/**
 * Each guard holds its own permission of a name, so a name is unique within its guard only.
 */
final class PermissionCrudControllerTest extends TestCase
{
    use CreatesAdminTestEnvironment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAdminTestEnvironment();
    }

    #[Test]
    public function a_name_of_the_other_guard_is_created_in_this_one(): void
    {
        $httpClient = $this->actingAs($this->adminUser, 'backpack');
        $response = $httpClient->post(
            '/admin/permission',
            [
                'name' => Permission::USER_VIEW->value,
                'guard_name' => 'web',
            ],
        );

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas(
            'permissions',
            ['name' => Permission::USER_VIEW->value, 'guard_name' => 'web'],
        );
    }

    #[Test]
    public function a_name_taken_in_the_same_guard_is_refused_with_that_guard(): void
    {
        $httpClient = $this->actingAs($this->adminUser, 'backpack');
        $httpClient = $httpClient->withSession(['backpack.language-switcher.locale' => 'cs']);
        $response = $httpClient->post(
            '/admin/permission',
            [
                'name' => Permission::USER_VIEW->value,
                'guard_name' => 'backpack',
            ],
        );

        $response->assertSessionHasErrors(
            ['name' => 'Hodnota pole „Název“ už je pro guard „backpack“ použitá.'],
        );
    }

    #[Test]
    public function renaming_to_a_name_taken_in_the_same_guard_is_refused(): void
    {
        $permission = PermissionModel::findByName(Permission::ROLE_VIEW->value, 'backpack');

        $httpClient = $this->actingAs($this->adminUser, 'backpack');
        $response = $httpClient->put(
            '/admin/permission/' . $permission->id,
            [
                'id' => $permission->id,
                'name' => Permission::USER_VIEW->value,
                'guard_name' => 'backpack',
            ],
        );

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseHas(
            'permissions',
            ['id' => $permission->id, 'name' => Permission::ROLE_VIEW->value],
        );
    }

    #[Test]
    public function an_update_keeping_its_own_name_passes(): void
    {
        $permission = PermissionModel::findByName(Permission::ROLE_VIEW->value, 'backpack');

        $httpClient = $this->actingAs($this->adminUser, 'backpack');
        $response = $httpClient->put(
            '/admin/permission/' . $permission->id,
            [
                'id' => $permission->id,
                'name' => Permission::ROLE_VIEW->value,
                'guard_name' => 'backpack',
            ],
        );

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    }
}
