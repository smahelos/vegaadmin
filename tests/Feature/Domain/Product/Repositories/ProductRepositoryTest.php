<?php

namespace Tests\Feature\Domain\Product\Repositories;

use App\Application\Product\Contracts\ProductRepositoryInterface;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithData;

class ProductRepositoryTest extends TestCase
{
    use RefreshDatabaseWithData;

    private ProductRepositoryInterface $productRepository;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->productRepository = $this->app->make(ProductRepositoryInterface::class);
        $this->user = User::factory()->create();
    }

    #[Test]
    public function can_find_user_products(): void
    {
        $otherUser = User::factory()->create();
        
        // Create products for our user
        Product::factory()->count(3)->create(['user_id' => $this->user->id]);
        
        // Create products for other user (should not be returned)
        Product::factory()->count(2)->create(['user_id' => $otherUser->id]);

        $products = $this->productRepository->findUserProducts($this->user);

        $this->assertCount(3, $products);
        $products->each(function ($product) {
            $this->assertEquals($this->user->id, $product->user_id);
        });
    }

    #[Test]
    public function products_are_ordered_by_created_at_desc(): void
    {
        $firstProduct = Product::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(2)
        ]);
        
        $secondProduct = Product::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDay()
        ]);
        
        $thirdProduct = Product::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()
        ]);

        $products = $this->productRepository->findUserProducts($this->user);

        $this->assertEquals($thirdProduct->id, $products->first()->id);
        $this->assertEquals($firstProduct->id, $products->last()->id);
    }

    #[Test]
    public function can_get_user_product_count(): void
    {
        $otherUser = User::factory()->create();
        
        Product::factory()->count(5)->create(['user_id' => $this->user->id]);
        Product::factory()->count(3)->create(['user_id' => $otherUser->id]);

        $count = $this->productRepository->findUserProducts($this->user)->count();

        $this->assertEquals(5, $count);
    }

    #[Test]
    public function can_find_product_by_id_for_user(): void
    {
        $product = Product::factory()->create(['user_id' => $this->user->id]);

        $foundProduct = $this->productRepository->findByIdForUserInt($product->id, $this->user->id);

        $this->assertEquals($product->id, $foundProduct->id);
        $this->assertEquals($this->user->id, $foundProduct->user_id);
    }

    #[Test]
    public function throws_exception_when_product_not_found_for_user(): void
    {
        $otherUser = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $otherUser->id]);

        $this->expectException(ModelNotFoundException::class);

        $this->productRepository->findByIdForUserInt($product->id, $this->user->id);
    }

    #[Test]
    public function throws_exception_when_product_id_does_not_exist(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->productRepository->findByIdForUserInt(999999, $this->user->id);
    }

    #[Test]
    public function can_create_product(): void
    {
        $productData = [
            'name' => 'Test Product',
            'price' => 99.99,
            'user_id' => $this->user->id,
            'description' => 'Test description'
        ];

        $product = $this->productRepository->create($productData);

        $this->assertInstanceOf(Product::class, $product);
        $this->assertEquals('Test Product', $product->name);
        $this->assertEquals(99.99, $product->price);
        $this->assertEquals($this->user->id, $product->user_id);
        $this->assertDatabaseHas('products', $productData);
    }

    #[Test]
    public function can_update_product(): void
    {
        $product = Product::factory()->create(['user_id' => $this->user->id]);

        $updateData = [
            'name' => 'Updated Product',
            'price' => 149.99
        ];

        $updatedProduct = $this->productRepository->update($product, $updateData);

        $this->assertEquals('Updated Product', $updatedProduct->name);
        $this->assertEquals(149.99, $updatedProduct->price);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Product',
            'price' => 149.99
        ]);
    }

    #[Test]
    public function update_returns_fresh_model(): void
    {
        $product = Product::factory()->create(['user_id' => $this->user->id]);
        $productId = $product->id;

        $updateData = ['name' => 'New Name'];

        $updatedProduct = $this->productRepository->update($product, $updateData);

        // Returned model should have new name
        $this->assertEquals('New Name', $updatedProduct->name);
        
        // Database should be updated
        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'name' => 'New Name'
        ]);
        
        // Fresh model should also have new name
        $freshProduct = Product::find($productId);
        $this->assertEquals('New Name', $freshProduct->name);
    }

    #[Test]
    public function can_delete_product(): void
    {
        $product = Product::factory()->create(['user_id' => $this->user->id]);

        $result = $this->productRepository->delete($product);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    #[Test]
    public function repository_interface_is_bound(): void
    {
        $repository = $this->app->make(ProductRepositoryInterface::class);
        
        $this->assertInstanceOf(ProductRepositoryInterface::class, $repository);
    }
}
