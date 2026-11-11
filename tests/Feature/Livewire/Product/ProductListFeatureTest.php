<?php

namespace Tests\Feature\Livewire\Product;

use App\Domain\User\Enums\Permission;
use App\Livewire\Product\ProductList;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductListFeatureTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setupTestRoutes();
        $userFactory = User::factory();
        $this->user = $userFactory->create();
        $this->user->givePermissionTo(Permission::PRODUCT_VIEW);
    }

    /**
     * Setup minimal routes needed for pagination component
     */
    private function setupTestRoutes(): void
    {
        $route = \Route::get('/test/products', function () {
            return 'test';
        });
        $route->name('frontend.product.index');

        $route = \Route::get('/test/product/{id}', function ($id) {
            return "product {$id}";
        });
        $route->name('frontend.product.show');

        $route = \Route::get('/test/product/{id}/edit', function ($id) {
            return "edit product {$id}";
        });
        $route->name('frontend.product.edit');

        $route = \Route::post('/test/product/{id}', function ($id) {
            return "delete product {$id}";
        });
        $route->name('frontend.product.destroy');

        $route = \Route::get('/test/product/create', function () {
            return 'create product';
        });
        $route->name('frontend.product.create');
    }

    #[Test]
    public function component_can_be_rendered(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ProductList::class);

        $component->assertStatus(200);
    }

    #[Test]
    public function component_displays_user_products_only(): void
    {
        $userFactory = User::factory();
        $otherUser = $userFactory->create();

        $productFactory = Product::factory();
        $userProduct = $productFactory->create(['user_id' => $this->user->id]);
        $otherUserProduct = $productFactory->create(['user_id' => $otherUser->id]);

        $this->actingAs($this->user);

        $component = Livewire::test(ProductList::class);
        $products = $component->viewData('products');

        $this->assertEquals(1, $products->count());
        $this->assertEquals($userProduct->id, $products->first()->id);
    }

    #[Test]
    public function component_handles_search_by_name(): void
    {
        $productFactory = Product::factory();
        $product1 = $productFactory->create(
            [
                'user_id' => $this->user->id,
                'name' => 'Awesome Product',
            ],
        );

        $product2 = $productFactory->create(
            [
                'user_id' => $this->user->id,
                'name' => 'Different Item',
            ],
        );

        $this->actingAs($this->user);

        $component = Livewire::test(ProductList::class);
        $component->set('search', 'Awesome');

        $products = $component->viewData('products');
        $this->assertEquals(1, $products->count());
        $this->assertEquals($product1->id, $products->first()->id);
    }

    #[Test]
    public function component_handles_search_by_description(): void
    {
        $productFactory = Product::factory();
        $product1 = $productFactory->create(
            [
                'user_id' => $this->user->id,
                'name' => 'Product A',
                'description' => 'This is an awesome product with great features',
            ],
        );

        $product2 = $productFactory->create(
            [
                'user_id' => $this->user->id,
                'name' => 'Product B',
                'description' => 'This is a different product',
            ],
        );

        $this->actingAs($this->user);

        $component = Livewire::test(ProductList::class);
        $component->set('search', 'awesome');

        $products = $component->viewData('products');
        $this->assertEquals(1, $products->count());
        $this->assertEquals($product1->id, $products->first()->id);
    }

    #[Test]
    public function component_filters_by_active_status(): void
    {
        $productFactory = Product::factory();
        $activeProduct = $productFactory->create([
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        $inactiveProduct = $productFactory->create([
            'user_id' => $this->user->id,
            'is_active' => false,
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(ProductList::class);
        $component->set('status', 'active');

        $products = $component->viewData('products');
        $this->assertEquals(1, $products->count());
        $this->assertEquals($activeProduct->id, $products->first()->id);
    }

    #[Test]
    public function component_filters_by_inactive_status(): void
    {
        $productFactory = Product::factory();
        $activeProduct = $productFactory->create([
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        $inactiveProduct = $productFactory->create([
            'user_id' => $this->user->id,
            'is_active' => false,
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(ProductList::class);
        $component->set('status', 'inactive');

        $products = $component->viewData('products');
        $this->assertEquals(1, $products->count());
        $this->assertEquals($inactiveProduct->id, $products->first()->id);
    }

    #[Test]
    public function sort_by_toggles_order_direction_for_same_field(): void
    {
        $productFactory = Product::factory();
        $productFactory = $productFactory->count(3);
        $productFactory->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user);

        $component = Livewire::test(ProductList::class);
        $component->set('orderBy', 'name');
        $component->set('orderAsc', true);

        $component->call('sortBy', 'name');

        $component->assertSet('orderBy', 'name');
        $component->assertSet('orderAsc', false);
    }

    #[Test]
    public function sort_by_sets_new_field_with_ascending_order(): void
    {
        $productFactory = Product::factory();
        $productFactory = $productFactory->count(3);
        $productFactory->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user);

        $component = Livewire::test(ProductList::class);
        $component->set('orderBy', 'created_at');
        $component->set('orderAsc', false);

        $component->call('sortBy', 'name');

        $component->assertSet('orderBy', 'name');
        $component->assertSet('orderAsc', true);
    }

    #[Test]
    public function component_handles_special_invoices_sorting(): void
    {
        $productFactory = Product::factory();
        $product1 = $productFactory->create(['user_id' => $this->user->id]);
        $product2 = $productFactory->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user);

        $component = Livewire::test(ProductList::class);
        $component->call('sortBy', 'invoices');

        $component->assertSet('orderBy', 'invoices');
        $component->assertSet('orderAsc', true);

        $products = $component->viewData('products');
        $this->assertIsArray($products->first()->invoices);
    }

    #[Test]
    public function updating_search_resets_pagination(): void
    {
        $this->actingAs($this->user);
        $productFactory = Product::factory();
        $productFactory = $productFactory->count(16);
        $productFactory->create(['user_id' => $this->user->id]);

        $component = Livewire::test(ProductList::class);
        $component->call('gotoPage', 2);
        $component->assertSet('paginators.page', 2);
        $component->set('search', 'test');
        $component->assertSet('paginators.page', 1);
    }

    #[Test]
    public function updating_status_resets_pagination(): void
    {
        $this->actingAs($this->user);
        $productFactory = Product::factory();
        $productFactory = $productFactory->count(16);
        $productFactory->create(['user_id' => $this->user->id]);

        $component = Livewire::test(ProductList::class);
        $component->call('gotoPage', 2);
        $component->assertSet('paginators.page', 2);
        $component->set('status', 'active');
        $component->assertSet('paginators.page', 1);
    }

    #[Test]
    public function reset_filters_clears_all_filters_and_pagination(): void
    {
        $this->actingAs($this->user);
        $productFactory = Product::factory();
        $productFactory = $productFactory->count(16);
        $productFactory->create(['user_id' => $this->user->id]);

        $component = Livewire::test(ProductList::class);
        $component->set('search', 'test');
        $component->set('status', 'active');
        $component->call('gotoPage', 2);
        $component->call('resetFilters');
        $component->assertSet('search', '');
        $component->assertSet('status', '');
        $component->assertSet('paginators.page', 1);
    }

    #[Test]
    public function component_displays_empty_state_when_no_products(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ProductList::class);

        $products = $component->viewData('products');
        $this->assertSame(0, $products->total());
    }

    #[Test]
    public function component_requires_authentication(): void
    {
        // Test that component works when authenticated
        $this->actingAs($this->user);
        $component = Livewire::test(ProductList::class);
        $component->assertStatus(200);

        $auth = auth();
        $auth->logout();

        $component = Livewire::test(ProductList::class);
        $component->assertStatus(403);
    }

    #[Test]
    public function component_uses_correct_view(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ProductList::class);

        $component->assertViewIs('livewire.product.product-list');
    }

    #[Test]
    public function component_passes_correct_data_to_view(): void
    {
        $productFactory = Product::factory();
        $productFactory->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user);

        $component = Livewire::test(ProductList::class);

        $products = $component->viewData('products');
        $this->assertSame(1, $products->total());
    }

    #[Test]
    public function component_counts_invoices_for_each_product(): void
    {
        $productFactory = Product::factory();
        $product = $productFactory->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user);

        $component = Livewire::test(ProductList::class);
        $products = $component->viewData('products');

        $this->assertSame([], $products->first()->invoices);
    }

    #[Test]
    public function component_applies_search_filter(): void
    {
        $productFactory = Product::factory();
        $productFactory->create(
            [
                'user_id' => $this->user->id,
                'name' => 'Matching Product',
            ],
        );

        $productFactory->create(
            [
                'user_id' => $this->user->id,
                'name' => 'Different Product',
            ],
        );

        $this->actingAs($this->user);

        $component = Livewire::test(ProductList::class);
        $component->set('search', 'Matching');

        $products = $component->viewData('products');
        $this->assertEquals(1, $products->count());
        $this->assertStringContainsString('Matching', $products->first()->name);
    }

    #[Test]
    public function component_preserves_url_state(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ProductList::class);
        $component->set('search', 'test');
        $component->set('status', 'active');
        $component->set('orderBy', 'name');
        $component->set('orderAsc', true);
        $component->call('gotoPage', 2);

        $component->assertSet('search', 'test');
        $component->assertSet('status', 'active');
        $component->assertSet('orderBy', 'name');
        $component->assertSet('orderAsc', true);
        $component->assertSet('paginators.page', 2);
    }

    #[Test]
    public function component_handles_empty_search_and_status(): void
    {
        $productFactory = Product::factory();
        $productFactory = $productFactory->count(2);
        $productFactory->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user);

        $component = Livewire::test(ProductList::class);
        $component->set('search', '');
        $component->set('status', '');

        $products = $component->viewData('products');
        $this->assertEquals(2, $products->count());
    }
}
