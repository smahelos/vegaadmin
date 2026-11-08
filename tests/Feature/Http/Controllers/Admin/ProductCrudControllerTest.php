<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\Product;
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
    public function store_keeps_the_active_checkbox(): void
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
                'is_active' => 1,
            ],
        );
        $httpResponse->assertSessionHasNoErrors();

        $this->assertDatabaseHas('products', ['name' => $name, 'is_active' => true]);
    }

    #[Test]
    public function store_answers_an_ajax_request_with_json(): void
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
            ['X-Requested-With' => 'XMLHttpRequest'],
        );

        $httpResponse->assertOk();
        $this->assertDatabaseHas('products', ['name' => $name]);
    }

    #[Test]
    public function update_keeps_the_owner_whatever_the_form_sends(): void
    {
        $this->actingAs($this->adminUser, 'backpack');
        $productFactory = Product::factory();
        $product = $productFactory->create(['user_id' => $this->regularUser->id]);
        $name = 'Renamed ' . uniqid();

        $httpResponse = $this->put(
            '/admin/product/' . $product->id,
            [
                'id' => $product->id,
                'name' => $name,
                'user_id' => $this->adminUser->id,
                'price' => 100,
                'currency' => 'CZK',
            ],
        );
        $httpResponse->assertSessionHasNoErrors();

        $this->assertDatabaseHas(
            'products',
            [
                'id' => $product->id,
                'name' => $name,
                'user_id' => $this->regularUser->id,
            ],
        );
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
