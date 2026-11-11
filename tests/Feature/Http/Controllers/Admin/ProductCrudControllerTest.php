<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Domain\User\Enums\Permission;
use App\Models\InvoiceProduct;
use App\Models\Permission as PermissionModel;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
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
            PermissionModel::findByName(Permission::PRODUCT_VIEW->value, 'backpack'),
            PermissionModel::findByName(Permission::PRODUCT_CREATE->value, 'backpack'),
            PermissionModel::findByName(Permission::PRODUCT_UPDATE->value, 'backpack'),
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

        // The gate checks the owner's own product.create, which needs a plan role
        $httpResponse->assertForbidden();
        $this->assertDatabaseMissing('products', ['name' => $name]);
    }

    #[Test]
    public function an_admin_updates_a_product_and_its_owner_stays(): void
    {
        $this->actingAs($this->adminUser, 'backpack');
        $userFactory = User::factory();
        $owner = $userFactory->create();
        $owner->assignRole(
            Role::findByName('frontend_user', 'web'),
        );
        $other = $userFactory->create();
        $productFactory = Product::factory();
        $product = $productFactory->create(['user_id' => $owner->id]);

        $httpResponse = $this->put(
            '/admin/product/' . $product->id,
            [
                'id' => $product->id,
                'name' => 'Renamed by the admin',
                'user_id' => $other->id,
                'price' => 100,
                'currency' => 'CZK',
            ],
        );

        $httpResponse->assertRedirect();
        $this->assertDatabaseHas(
            'products',
            [
                'id' => $product->id,
                'name' => 'Renamed by the admin',
                'user_id' => $owner->id,
            ],
        );
    }

    #[Test]
    public function an_admin_deletes_a_product(): void
    {
        $this->actingAs($this->adminUser, 'backpack');
        $productFactory = Product::factory();
        $product = $productFactory->create();

        $response = $this->deleteJson('/admin/product/' . $product->id);

        $response->assertOk();
        $this->assertModelMissing($product);
    }

    #[Test]
    public function a_product_on_an_invoice_line_answers_the_delete_with_a_notification(): void
    {
        $this->actingAs($this->adminUser, 'backpack');
        $invoiceProductFactory = InvoiceProduct::factory();
        $invoiceProduct = $invoiceProductFactory->create();

        $response = $this->deleteJson('/admin/product/' . $invoiceProduct->product_id);

        $response->assertOk();
        $response->assertExactJson(
            ['error' => [__('products.messages.error_delete_invoices')]],
        );
        $this->assertDatabaseHas('products', ['id' => $invoiceProduct->product_id]);
    }
}
