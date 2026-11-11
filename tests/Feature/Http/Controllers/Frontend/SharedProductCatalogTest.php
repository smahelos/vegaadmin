<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Frontend;

use App\Domain\User\Enums\Permission;
use App\Livewire\Product\ProductList;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SharedProductCatalogTest extends TestCase
{
    #[Test]
    public function product_forms_no_longer_need_a_supplier(): void
    {
        $userFactory = User::factory();
        $user = $userFactory->create();
        $user->givePermissionTo(Permission::PRODUCT_CREATE, Permission::PRODUCT_UPDATE);
        $productFactory = Product::factory();
        $product = $productFactory->create(['user_id' => $user->id]);
        $this->actingAs($user, 'web');
        $httpResponse = $this->get(
            route('frontend.product.create', ['locale' => 'cs']),
        );
        $httpResponse = $httpResponse->assertOk();
        $httpResponse = $httpResponse->assertSee('name="name"', false);
        $httpResponse = $httpResponse->assertSee('name="price"', false);
        $httpResponse->assertDontSee('supplier_id');
        $httpResponse = $this->get(
            route('frontend.product.edit', ['locale' => 'cs', 'id' => $product->id]),
        );
        $httpResponse = $httpResponse->assertOk();
        $httpResponse = $httpResponse->assertSee($product->name);
        $httpResponse->assertDontSee('supplier_id');
    }

    #[Test]
    public function the_product_list_keeps_the_catalog_scoped_to_the_user(): void
    {
        $userFactory = User::factory();
        $user = $userFactory->create();
        $user->givePermissionTo(Permission::PRODUCT_VIEW);
        $productFactory = Product::factory();
        $product = $productFactory->create(['user_id' => $user->id, 'name' => 'Visible shared product']);
        $productFactory->create(['name' => 'Other private product']);
        Livewire::actingAs($user);
        $component = Livewire::test(ProductList::class);
        $component->set('search', 'product');
        $component->assertSee($product->name);
        $component->assertDontSee('Other private product');
        $component->assertDontSee('supplier_id');
    }
}
