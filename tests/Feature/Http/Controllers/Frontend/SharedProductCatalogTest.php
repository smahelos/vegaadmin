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
        $userFactory = User::factory();
        $user = $userFactory->create();
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
    public function reading_and_updating_a_product_preserves_its_identity_without_a_supplier_relation(): void
    {
        $productFactory = Product::factory();
        $product = $productFactory->create(['price' => '123.45', 'currency' => 'CZK']);
        $productDtoReadRepository = app(ProductDtoReadRepositoryInterface::class);
        $dto = $productDtoReadRepository->findByIdForUser(
            ProductId::fromInt($product->id),
            UserId::fromInt($product->user_id),
        );
        $this->assertSame('123.45', $dto->price->getAmount());
        $productApplicationService = app(ProductApplicationServiceInterface::class);
        $updated = $productApplicationService->updateProduct(
            $product->id,
            ['name' => 'Updated shared product', 'price' => '200.50'],
            $product->user_id,
        );
        $this->assertSame($dto->id, $updated->id);
        $this->assertSame('200.5', $updated->price->getAmount());
        $this->assertDatabaseHas(
            'products',
            [
                'id' => $product->id,
                'user_id' => $product->user_id,
                'name' => 'Updated shared product',
            ],
        );
    }

    #[Test]
    public function the_product_list_keeps_the_catalog_scoped_to_the_user(): void
    {
        $userFactory = User::factory();
        $user = $userFactory->create();
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
