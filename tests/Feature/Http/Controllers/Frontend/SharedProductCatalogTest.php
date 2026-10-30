<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Frontend;

use App\Application\Product\Contracts\ProductApplicationServiceInterface;
use App\Domain\Product\Contracts\ProductDtoReadRepositoryInterface;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\User\ValueObjects\UserId;
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
        $user = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user, 'web');
        $this->get(route('frontend.product.create', ['locale' => 'cs']))->assertOk()
            ->assertSee('name="name"', false)->assertSee('name="price"', false)->assertDontSee('supplier_id');
        $this->get(route('frontend.product.edit', ['locale' => 'cs', 'id' => $product->id]))->assertOk()
            ->assertSee($product->name)->assertDontSee('supplier_id');
    }

    #[Test]
    public function reading_and_updating_a_product_preserves_its_identity_without_a_supplier_relation(): void
    {
        $product = Product::factory()->create(['price' => '123.45', 'currency' => 'CZK']);
        $dto = app(ProductDtoReadRepositoryInterface::class)->findByIdForUser(
            ProductId::fromInt($product->id),
            UserId::fromInt($product->user_id),
        );
        $this->assertSame('123.45', $dto->price->getAmount());
        $updated = app(ProductApplicationServiceInterface::class)->updateProduct(
            $product->id,
            ['name' => 'Updated shared product', 'price' => '200.50'],
            $product->user_id,
        );
        $this->assertSame($dto->id, $updated->id);
        $this->assertSame('200.5', $updated->price->getAmount());
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'user_id' => $product->user_id,
            'name' => 'Updated shared product',
        ]);
    }

    #[Test]
    public function the_product_list_keeps_the_catalog_scoped_to_the_user(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $user->id, 'name' => 'Visible shared product']);
        Product::factory()->create(['name' => 'Other private product']);
        Livewire::actingAs($user)->test(ProductList::class)
            ->set('search', 'product')->assertSee($product->name)->assertDontSee('Other private product')->assertDontSee('supplier_id');
    }
}
