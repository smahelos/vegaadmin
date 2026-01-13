<?php

namespace Tests\Feature\Domain\Product\Services;

use App\Domain\Product\Contracts\ProductServiceInterface;
use App\Domain\Product\DTO\ProductDTO;
use App\Models\Product;
use App\Domain\Product\DTO\ProductWriteData;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\User\ValueObjects\UserId;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithData;

class ProductServiceTest extends TestCase
{
    use RefreshDatabaseWithData;

    private ProductServiceInterface $productService;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->productService = $this->app->make(ProductServiceInterface::class);
        $this->user = User::factory()->create();
    }

    #[Test]
    public function can_create_product(): void
    {
        $productData = new ProductWriteData(
            name: 'Test Product',
            price: 99.99,
            description: 'Test description'
        );

        $product = $this->productService->createProduct($productData, UserId::fromInt($this->user->id));

        $this->assertInstanceOf(ProductDTO::class, $product);
        $this->assertEquals('Test Product', $product->name);
        $this->assertEquals(99.99, $product->price->getAmount());
        $this->assertEquals($this->user->id, $product->user_id);
        $this->assertTrue($product->is_default); // First product should be default
    }

    #[Test]
    public function first_product_is_set_as_default(): void
    {
        $productData = new ProductWriteData(
            name: 'First Product',
            price: 50.00
        );

        $product = $this->productService->createProduct($productData, UserId::fromInt($this->user->id));

        $this->assertTrue($product->is_default);
    }

    #[Test]
    public function second_product_is_not_default(): void
    {
        // Create first product
        Product::factory()->create(['user_id' => $this->user->id, 'is_default' => true]);

        $productData = new ProductWriteData(
            name: 'Second Product',
            price: 75.00
        );

        $product = $this->productService->createProduct($productData, UserId::fromInt($this->user->id));

        // Should be false or null, not true
        $this->assertNotTrue($product->is_default);
    }

    #[Test]
    public function generates_slug_from_name(): void
    {
        $productData = new ProductWriteData(
            name: 'Test Product Name',
            price: 25.00
        );

        $product = $this->productService->createProduct($productData, UserId::fromInt($this->user->id));

        $this->assertEquals('test-product-name', $product->slug);
    }

    #[Test]
    public function uses_provided_slug(): void
    {
        $productData = new ProductWriteData(
            name: 'Test Product',
            slug: 'custom-slug',
            price: 25.00
        );

        $product = $this->productService->createProduct($productData, UserId::fromInt($this->user->id));

        $this->assertEquals('custom-slug', $product->slug);
    }

    #[Test]
    public function can_update_product(): void
    {
        $product = Product::factory()->create(['user_id' => $this->user->id]);

        $updateData = new ProductWriteData(
            name: 'Updated Product',
            price: 149.99,
            is_active: 1
        );

        $updatedProduct = $this->productService->updateProduct(ProductId::fromInt($product->id), $updateData);

        $this->assertEquals('Updated Product', $updatedProduct->name);
        $this->assertEquals(149.99, $updatedProduct->price->getAmount());
        $this->assertTrue($updatedProduct->is_active);
    }

    #[Test]
    public function handles_boolean_fields_correctly(): void
    {
        $product = Product::factory()->create(['user_id' => $this->user->id]);

        $updateData = new ProductWriteData(
            name: 'Test Product',
            is_default: true,
            is_active: false
        );

        $updatedProduct = $this->productService->updateProduct(ProductId::fromInt($product->id), $updateData);

        $this->assertTrue($updatedProduct->is_default);
        $this->assertFalse($updatedProduct->is_active);
    }

    #[Test]
    public function can_delete_product(): void
    {
        $product = Product::factory()->create(['user_id' => $this->user->id]);

        $this->productService->deleteProduct(ProductId::fromInt($product->id));

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }
}
