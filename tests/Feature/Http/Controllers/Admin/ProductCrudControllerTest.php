<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\ProductCategory;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use Tests\Traits\CreatesAdminTestEnvironment;

class ProductCrudControllerTest extends TestCase
{
    use CreatesAdminTestEnvironment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpAdminTestEnvironment();
        $this->regularUser->givePermissionTo(
            Permission::findByName('can_view_product', 'backpack'),
            Permission::findByName('can_create_product', 'backpack'),
            Permission::findByName('can_update_product', 'backpack'),
        );
    }

    #[Test]
    public function create_form_offers_product_categories(): void
    {
        $this->actingAs($this->adminUser, 'backpack');
        $productCategoryFactory = ProductCategory::factory();
        $category = $productCategoryFactory->create(['name' => 'Category ' . uniqid()]);

        $response = $this->get('/admin/product/create');

        $response->assertOk();
        $response->assertSee('<option value="' . $category->id . '"', false);
        $response->assertSee($category->name);
    }

    #[Test]
    public function store_creates_the_product_for_the_chosen_owner(): void
    {
        $this->actingAs($this->adminUser, 'backpack');
        $name = 'Product ' . uniqid();

        $httpResponse = $this->post(
            '/admin/product',
            [
                'name' => $name,
                'user_id' => $this->regularUser->id,
                'price' => 100,
                'currency' => 'CZK',
            ],
        );
        $httpResponse = $httpResponse->assertSessionHasNoErrors();
        $httpResponse->assertRedirect();

        $this->assertDatabaseHas('products', ['name' => $name, 'user_id' => $this->regularUser->id]);
    }

    #[Test]
    public function store_requires_an_owner(): void
    {
        $this->actingAs($this->adminUser, 'backpack');

        $httpResponse = $this->post(
            '/admin/product',
            [
                'name' => 'Product ' . uniqid(),
                'price' => 100,
                'currency' => 'CZK',
            ],
        );

        $httpResponse->assertSessionHasErrors('user_id');
    }

    #[Test]
    public function an_owner_without_a_plan_role_gets_no_product_whatever_the_admin_may_do(): void
    {
        $this->actingAs($this->adminUser, 'backpack');
        $userFactory = User::factory();
        $owner = $userFactory->create();
        $name = 'Product ' . uniqid();

        $httpResponse = $this->post(
            '/admin/product',
            [
                'name' => $name,
                'user_id' => $owner->id,
                'price' => 100,
                'currency' => 'CZK',
            ],
        );

        $httpResponse->assertRedirect();
        $this->assertDatabaseMissing('products', ['name' => $name]);
    }
}
