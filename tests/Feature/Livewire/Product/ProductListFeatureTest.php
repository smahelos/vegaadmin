<?php

namespace Tests\Feature\Livewire\Product;

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
        $this->user = User::factory()->create();
    }

    /**
     * Setup minimal routes needed for pagination component
     */
    private function setupTestRoutes(): void
    {
        \Route::get('/test/products', function () {
            return 'test';
        })->name('frontend.product.index');

        \Route::get('/test/product/{id}', function ($id) {
            return "product {$id}";
        })->name('frontend.product.show');

        \Route::get('/test/product/{id}/edit', function ($id) {
            return "edit product {$id}";
        })->name('frontend.product.edit');

        \Route::post('/test/product/{id}', function ($id) {
            return "delete product {$id}";
        })->name('frontend.product.destroy');

        \Route::get('/test/product/create', function () {
            return 'create product';
        })->name('frontend.product.create');
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
        $otherUser = User::factory()->create();

        $userProduct = Product::factory()->create(['user_id' => $this->user->id]);
        $otherUserProduct = Product::factory()->create(['user_id' => $otherUser->id]);

        $this->actingAs($this->user);

        $component = Livewire::test(ProductList::class);
        $products = $component->viewData('products');

        $this->assertEquals(1, $products->count());
        $this->assertEquals($userProduct->id, $products->first()->id);
    }

    #[Test]
    public function component_handles_search_by_name(): void
    {
        $product1 = Product::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Awesome Product',
        ]);

        $product2 = Product::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Different Item',
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(ProductList::class)
            ->set('search', 'Awesome');

        $products = $component->viewData('products');
        $this->assertEquals(1, $products->count());
        $this->assertEquals($product1->id, $products->first()->id);
    }

    #[Test]
    public function component_handles_search_by_description(): void
    {
        $product1 = Product::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Product A',
            'description' => 'This is an awesome product with great features',
        ]);

        $product2 = Product::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Product B',
            'description' => 'This is a different product',
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(ProductList::class)
            ->set('search', 'awesome');

        $products = $component->viewData('products');
        $this->assertEquals(1, $products->count());
        $this->assertEquals($product1->id, $products->first()->id);
    }

    #[Test]
    public function component_filters_by_active_status(): void
    {
        $activeProduct = Product::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        $inactiveProduct = Product::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => false,
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(ProductList::class)
            ->set('status', 'active');

        $products = $component->viewData('products');
        $this->assertEquals(1, $products->count());
        $this->assertEquals($activeProduct->id, $products->first()->id);
    }

    #[Test]
    public function component_filters_by_inactive_status(): void
    {
        $activeProduct = Product::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        $inactiveProduct = Product::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => false,
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(ProductList::class)
            ->set('status', 'inactive');

        $products = $component->viewData('products');
        $this->assertEquals(1, $products->count());
        $this->assertEquals($inactiveProduct->id, $products->first()->id);
    }

    #[Test]
    public function sort_by_toggles_order_direction_for_same_field(): void
    {
        Product::factory()->count(3)->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user);

        $component = Livewire::test(ProductList::class)
            ->set('orderBy', 'name')
            ->set('orderAsc', true);

        $component->call('sortBy', 'name');

        $component->assertSet('orderBy', 'name');
        $component->assertSet('orderAsc', false);
    }

    #[Test]
    public function sort_by_sets_new_field_with_ascending_order(): void
    {
        Product::factory()->count(3)->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user);

        $component = Livewire::test(ProductList::class)
            ->set('orderBy', 'created_at')
            ->set('orderAsc', false);

        $component->call('sortBy', 'name');

        $component->assertSet('orderBy', 'name');
        $component->assertSet('orderAsc', true);
    }

    #[Test]
    public function component_handles_special_invoices_sorting(): void
    {
        $product1 = Product::factory()->create(['user_id' => $this->user->id]);
        $product2 = Product::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user);

        $component = Livewire::test(ProductList::class)
            ->call('sortBy', 'invoices');

        $component->assertSet('orderBy', 'invoices');
        $component->assertSet('orderAsc', true);

        // Verify that products are loaded with invoices_count
        $products = $component->viewData('products');
        $this->assertNotNull($products->first()->invoices_count);
    }

    #[Test]
    public function updating_search_resets_pagination(): void
    {
        $this->actingAs($this->user);
        Product::factory()->count(16)->create(['user_id' => $this->user->id]);

        Livewire::test(ProductList::class)
            ->call('gotoPage', 2)
            ->assertSet('paginators.page', 2)
            ->set('search', 'test')
            ->assertSet('paginators.page', 1);
    }

    #[Test]
    public function updating_status_resets_pagination(): void
    {
        $this->actingAs($this->user);
        Product::factory()->count(16)->create(['user_id' => $this->user->id]);

        Livewire::test(ProductList::class)
            ->call('gotoPage', 2)
            ->assertSet('paginators.page', 2)
            ->set('status', 'active')
            ->assertSet('paginators.page', 1);
    }

    #[Test]
    public function reset_filters_clears_all_filters_and_pagination(): void
    {
        $this->actingAs($this->user);
        Product::factory()->count(16)->create(['user_id' => $this->user->id]);

        Livewire::test(ProductList::class)
            ->set('search', 'test')
            ->set('status', 'active')
            ->call('gotoPage', 2)
            ->call('resetFilters')
            ->assertSet('search', '')
            ->assertSet('status', '')
            ->assertSet('paginators.page', 1);
    }

    #[Test]
    public function component_displays_empty_state_when_no_products(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ProductList::class);

        $hasData = $component->viewData('hasData');
        $this->assertFalse($hasData);
    }

    #[Test]
    public function component_requires_authentication(): void
    {
        // Test that component works when authenticated
        $this->actingAs($this->user);
        $component = Livewire::test(ProductList::class);
        $component->assertStatus(200);

        // Test behavior when not authenticated (Auth::id() returns null)
        auth()->logout();

        $component = Livewire::test(ProductList::class);
        $products = $component->viewData('products');

        // Should return empty collection when no user authenticated
        $this->assertEquals(0, $products->count());
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
        Product::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user);

        $component = Livewire::test(ProductList::class);

        $component->assertViewHas('products');
        $component->assertViewHas('hasData', true);
    }

    #[Test]
    public function component_counts_invoices_for_each_product(): void
    {
        $product = Product::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user);

        $component = Livewire::test(ProductList::class);
        $products = $component->viewData('products');

        // Check that invoices_count is loaded
        $this->assertNotNull($products->first()->invoices_count);
        $this->assertEquals(0, $products->first()->invoices_count);
    }

    #[Test]
    public function component_applies_search_filter(): void
    {
        Product::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Matching Product',
        ]);

        Product::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Different Product',
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(ProductList::class)
            ->set('search', 'Matching');

        $products = $component->viewData('products');
        $this->assertEquals(1, $products->count());
        $this->assertStringContainsString('Matching', $products->first()->name);
    }

    #[Test]
    public function component_preserves_url_state(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ProductList::class)
            ->set('search', 'test')
            ->set('status', 'active')
            ->set('orderBy', 'name')
            ->set('orderAsc', true)
            ->call('gotoPage', 2);

        $component->assertSet('search', 'test');
        $component->assertSet('status', 'active');
        $component->assertSet('orderBy', 'name');
        $component->assertSet('orderAsc', true);
        $component->assertSet('paginators.page', 2);
    }

    #[Test]
    public function component_handles_empty_search_and_status(): void
    {
        Product::factory()->count(2)->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user);

        $component = Livewire::test(ProductList::class)
            ->set('search', '')
            ->set('status', '');

        $products = $component->viewData('products');
        $this->assertEquals(2, $products->count());
    }
}
