<?php

namespace Tests\Feature\Infrastructure\Authorization\Policies\Product;

use App\Infrastructure\Authorization\Policies\Product\ProductPolicy;
use App\Models\Product;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithData;

class ProductPolicyTest extends TestCase
{
    use RefreshDatabaseWithData;

    private ProductPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new ProductPolicy();
    }

    #[Test]
    public function view_any_allows_admin_or_roles_or_permission(): void
    {
        $admin = User::factory()->create();
        $this->makeRole($admin, 'admin');
        $this->assertTrue($this->policy->viewAny($admin));

        $frontend = User::factory()->create();
        $this->makeRole($frontend, 'frontend_user');
        $this->assertTrue($this->policy->viewAny($frontend));

        $permUser = User::factory()->create();
        Permission::firstOrCreate(['name' => 'frontend.can_create_edit_product', 'guard_name' => 'backpack']);
        $permUser->givePermissionTo('frontend.can_create_edit_product');
        $this->assertTrue($this->policy->viewAny($permUser));
    }

    #[Test]
    public function view_checks_ownership_or_admin(): void
    {
        $admin = User::factory()->create();
        $this->makeRole($admin, 'admin');
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $owner->id]);
        $this->assertTrue($this->policy->view($admin, $product));
        $this->assertTrue($this->policy->view($owner, $product));
        $this->assertFalse($this->policy->view($other, $product));
    }

    #[Test]
    public function update_checks_ownership_or_admin(): void
    {
        $admin = User::factory()->create();
        $this->makeRole($admin, 'admin');
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $owner->id]);
        $this->assertTrue($this->policy->update($admin, $product));
        $this->assertTrue($this->policy->update($owner, $product));
        $this->assertFalse($this->policy->update($other, $product));
    }

    #[Test]
    public function delete_checks_ownership_roles_or_admin(): void
    {
        $admin = User::factory()->create();
        $this->makeRole($admin, 'admin');
        $frontend = User::factory()->create();
        $this->makeRole($frontend, 'frontend_user');
        $owner = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $owner->id]);
        $this->assertTrue($this->policy->delete($admin, $product));
        $this->assertFalse($this->policy->delete($frontend, $product)); // not owner
        $ownedProduct = Product::factory()->create(['user_id' => $frontend->id]);
        $this->assertTrue($this->policy->delete($frontend, $ownedProduct));
        $this->assertTrue($this->policy->delete($owner, $product));
    }

    private function makeRole(User $user, string $roleName): void
    {
        $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'backpack']);
        $user->assignRole($role);
    }
}
