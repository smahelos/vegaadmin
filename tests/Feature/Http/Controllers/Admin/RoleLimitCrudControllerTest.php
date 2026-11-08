<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\RoleLimit;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\CreatesAdminTestEnvironment;

/**
 * PRD_ENTITY_LIMITS A14: an admin sets number, unlimited or not allowed per entity for a web role.
 */
final class RoleLimitCrudControllerTest extends TestCase
{
    use CreatesAdminTestEnvironment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpAdminTestEnvironment();
    }

    #[Test]
    public function store_saves_a_number_as_the_limit_unlimited_as_null_and_not_allowed_as_zero(): void
    {
        $role = $this->webRole();

        $httpClient = $this->actingAs($this->adminUser, 'backpack');
        $response = $httpClient->post(
            '/admin/role-limit',
            [
                'role_id' => $role->id,
                ...$this->limits(),
            ],
        );

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas(
            'role_limits',
            [
                'role_id' => $role->id,
                'invoices' => 25,
                'expenses' => null,
                'businesses' => 0,
                'contacts' => 3,
                'products' => null,
            ],
        );
    }

    #[Test]
    public function store_requires_a_number_for_the_limited_mode(): void
    {
        $role = $this->webRole();

        $httpClient = $this->actingAs($this->adminUser, 'backpack');
        $response = $httpClient->post(
            '/admin/role-limit',
            [
                'role_id' => $role->id,
                ...$this->limits(),
                'invoices' => '',
            ],
        );

        $response->assertSessionHasErrors('invoices');
        $this->assertDatabaseMissing('role_limits', ['role_id' => $role->id]);
    }

    #[Test]
    public function store_rejects_an_admin_role_and_a_role_that_already_has_limits(): void
    {
        $adminRole = Role::findByName('admin', 'backpack');
        $limitedRole = Role::findByName('frontend_user', 'web');

        $httpClient = $this->actingAs($this->adminUser, 'backpack');
        foreach ([$adminRole, $limitedRole] as $role) {
            $response = $httpClient->post(
                '/admin/role-limit',
                [
                    'role_id' => $role->id,
                    ...$this->limits(),
                ],
            );

            $response->assertSessionHasErrors('role_id');
        }

        $this->assertDatabaseMissing('role_limits', ['role_id' => $adminRole->id]);
    }

    #[Test]
    public function update_changes_the_limits_but_not_the_role(): void
    {
        $role = $this->webRole();
        $roleLimit = RoleLimit::create(['role_id' => $role->id, 'invoices' => 5]);
        $otherRole = $this->webRole();

        $httpClient = $this->actingAs($this->adminUser, 'backpack');
        $response = $httpClient->put(
            '/admin/role-limit/' . $roleLimit->id,
            [
                'id' => $roleLimit->id,
                'role_id' => $otherRole->id,
                ...$this->limits(),
                'invoices_mode' => 'unlimited',
                'invoices' => '7',
            ],
        );

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $roleLimit->refresh();
        $this->assertSame($role->id, $roleLimit->role_id);
        $this->assertNull($roleLimit->invoices);
        $this->assertSame(0, $roleLimit->businesses);
    }

    #[Test]
    public function the_create_form_offers_only_web_roles_without_limits(): void
    {
        $role = $this->webRole();

        $httpClient = $this->actingAs($this->adminUser, 'backpack');
        $response = $httpClient->get('/admin/role-limit/create');

        $response->assertOk();
        $response->assertSee('<option value="' . $role->id . '"', false);
        $limitedRole = Role::findByName('frontend_user', 'web');
        $response->assertDontSee('<option value="' . $limitedRole->id . '"', false);
    }

    #[Test]
    public function the_edit_form_shows_the_role_and_selects_the_stored_mode(): void
    {
        $role = $this->webRole();
        $roleLimit = RoleLimit::create(
            [
                'role_id' => $role->id,
                'invoices' => 0,
                'expenses' => 12,
            ],
        );

        $httpClient = $this->actingAs($this->adminUser, 'backpack');
        $response = $httpClient->get('/admin/role-limit/' . $roleLimit->id . '/edit');

        $response->assertOk();
        $response->assertSee($role->name);
        $response->assertDontSee('name="role_id"', false);
        $response->assertSee('name="invoices_mode"', false);
        $response->assertSee('<option value="not_allowed" selected', false);
        $response->assertSee('name="expenses" value="12"', false);
    }

    #[Test]
    public function the_list_shows_unlimited_and_not_allowed_by_name(): void
    {
        $role = $this->webRole();
        RoleLimit::create(['role_id' => $role->id, 'invoices' => 0]);

        $httpClient = $this->actingAs($this->adminUser, 'backpack');
        $response = $httpClient->postJson('/admin/role-limit/search');

        $response->assertOk();
        $response->assertSee($role->name);
        $response->assertSee(
            __('role_limits.statuses.not_allowed'),
        );
        $response->assertSee(
            __('role_limits.statuses.unlimited'),
        );
    }

    #[Test]
    public function writes_are_denied_with_the_view_permission_only(): void
    {
        $role = $this->webRole();
        $roleLimit = RoleLimit::create(['role_id' => $role->id]);
        $user = $this->backendUser(['can_view_role_limit']);

        $httpClient = $this->actingAs($user, 'backpack');
        $response = $httpClient->get('/admin/role-limit/create');
        $response->assertForbidden();
        $response = $httpClient->get('/admin/role-limit/' . $roleLimit->id . '/edit');
        $response->assertForbidden();
        $response = $httpClient->delete('/admin/role-limit/' . $roleLimit->id);
        $response->assertForbidden();

        $this->assertModelExists($roleLimit);
    }

    /**
     * @return array<string, string>
     */
    private function limits(): array
    {
        return [
            'invoices_mode' => 'limited',
            'invoices' => '25',
            'expenses_mode' => 'unlimited',
            'businesses_mode' => 'not_allowed',
            'businesses' => '9',
            'contacts_mode' => 'limited',
            'contacts' => '3',
            'products_mode' => 'unlimited',
        ];
    }

    private function webRole(): Role
    {
        return Role::create(['name' => 'plan_' . uniqid(), 'guard_name' => 'web']);
    }

    /**
     * @param  list<string>  $permissions
     */
    private function backendUser(array $permissions): User
    {
        $userFactory = User::factory();
        $user = $userFactory->create();
        $user->assignRole(
            Role::findByName('backend_user', 'backpack'),
        );
        foreach (['backpack.access', ...$permissions] as $permission) {
            $user->givePermissionTo(
                Permission::findByName($permission, 'backpack'),
            );
        }

        return $user;
    }
}
