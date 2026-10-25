<?php

namespace Tests\Feature\Domain\Product\Repositories;

use App\Application\Product\Contracts\ProductRepositoryInterface;
use App\Models\Product;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductRepositoryTest extends TestCase
{
    private ProductRepositoryInterface $productRepository;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->productRepository = $this->app->make(ProductRepositoryInterface::class);
        $this->user = User::factory()->create();
    }

    #[Test]
    public function can_create_product(): void
    {
        $productData = [
            'name' => 'Test Product',
            'price' => 99.99,
            'currency' => 'CZK',
            'user_id' => $this->user->id,
            'description' => 'Test description',
        ];

        $product = $this->productRepository->create($productData);

        $this->assertInstanceOf(Product::class, $product);
        $this->assertEquals('Test Product', $product->name);
        $this->assertEquals(99.99, $product->price);
        $this->assertEquals($this->user->id, $product->user_id);
        $this->assertDatabaseHas('products', $productData);
    }

    #[Test]
    public function repository_interface_is_bound(): void
    {
        $repository = $this->app->make(ProductRepositoryInterface::class);

        $this->assertInstanceOf(ProductRepositoryInterface::class, $repository);
    }
}
