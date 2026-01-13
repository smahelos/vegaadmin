<?php

namespace Tests\Feature\Traits;

use App\Infrastructure\Authorization\Services\CrudAccessService;
use App\Infrastructure\Authorization\Adapters\User\SpatieAuthorizationAdapter;
use App\Models\User;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanel;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CrudAccessServiceFeatureTest extends TestCase
{
    use RefreshDatabase;

    private CrudAccessService $service;
    private CrudPanel $crud;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CrudAccessService(new SpatieAuthorizationAdapter());

        // Minimal CrudPanel instance; we'll stub access arrays via reflection
        $this->crud = new CrudPanel();

        // Ensure roles/permissions exist
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'backpack']);
        foreach (['can_view_user', 'can_create_edit_user'] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'backpack']);
        }
    }

    private function setCrudModel(string $modelClass): void
    {
        $this->crud->setModel($modelClass);
        // Also set the global CrudPanel facade model so CRUD::getModel() returns an instance
        CRUD::setModel($modelClass);
    }

    private function hasAccess(string $operation): bool
    {
        // CrudPanel has method hasAccess
        return $this->crud->hasAccess($operation);
    }

    #[Test]
    public function denies_all_access_by_default(): void
    {
        $this->setCrudModel(\App\Models\User::class);
        $user = User::factory()->create();

        $this->service->configureCrudAccess($this->crud, $user->id);

        foreach (['list','show','create','update','delete'] as $op) {
            $this->assertFalse($this->hasAccess($op));
        }
    }

    #[Test]
    public function allows_view_access_with_view_permission(): void
    {
        $this->setCrudModel(\App\Models\User::class);
        $user = User::factory()->create();
        $perm = Permission::where('name', 'can_view_user')->where('guard_name', 'backpack')->first();
        $user->givePermissionTo($perm);

        $this->service->configureCrudAccess($this->crud, $user->id);
        $this->assertTrue($this->hasAccess('list'));
        $this->assertTrue($this->hasAccess('show'));
        $this->assertFalse($this->hasAccess('create'));
    }

    #[Test]
    public function allows_full_access_with_create_edit_permission(): void
    {
        $this->setCrudModel(\App\Models\User::class);
        $user = User::factory()->create();
        $perm = Permission::where('name', 'can_create_edit_user')->where('guard_name', 'backpack')->first();
        $user->givePermissionTo($perm);

        $this->service->configureCrudAccess($this->crud, $user->id);
        foreach (['list','show','create','update','delete'] as $op) {
            $this->assertTrue($this->hasAccess($op));
        }
    }

    #[Test]
    public function admin_role_allows_full_access(): void
    {
        $this->setCrudModel(\App\Models\User::class);
        $admin = User::factory()->create();
        $role = Role::where('name', 'admin')->where('guard_name', 'backpack')->first();
        $admin->assignRole($role);

        $this->service->configureCrudAccess($this->crud, $admin->id);
        foreach (['list','show','create','update','delete'] as $op) {
            $this->assertTrue($this->hasAccess($op));
        }
    }

    #[Test]
    public function respects_entity_table_for_permissions(): void
    {
        // Switch model to Client to ensure singular entity mapping handled by service
        $this->setCrudModel(\App\Models\Client::class);
        $user = User::factory()->create();
        // For client view permission, adapter checks can_view_client; create_edit permission is can_create_edit_client
        Permission::firstOrCreate(['name' => 'can_view_client', 'guard_name' => 'backpack']);
        $perm = Permission::where('name', 'can_view_client')->where('guard_name', 'backpack')->first();
        $user->givePermissionTo($perm);

        $this->service->configureCrudAccess($this->crud, $user->id);
        $this->assertTrue($this->hasAccess('list'));
        $this->assertFalse($this->hasAccess('create'));
    }
}
