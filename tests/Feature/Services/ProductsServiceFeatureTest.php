<?php

namespace Tests\Feature\Services;

use App\Models\ProductCategory;
use App\Models\Supplier;
use App\Models\User;
use App\Services\ProductsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductsServiceFeatureTest extends TestCase
{
    use RefreshDatabase;

    private ProductsService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProductsService();
        $this->user = User::factory()->create();
        
        // Clear cache before each test
        Cache::flush();
    }

    #[Test]
    public function get_all_categories_returns_cached_results(): void
    {
        // Create test categories
        $category1 = ProductCategory::factory()->create(['name' => 'Electronics']);
        $category2 = ProductCategory::factory()->create(['name' => 'Books']);
        $category3 = ProductCategory::factory()->create(['name' => 'Clothing']);

        $result = $this->service->getAllCategories();

        $this->assertIsArray($result);
        $this->assertArrayHasKey($category1->id, $result);
        $this->assertArrayHasKey($category2->id, $result);
        $this->assertArrayHasKey($category3->id, $result);
        $this->assertEquals('Electronics', $result[$category1->id]);
        $this->assertEquals('Books', $result[$category2->id]);
        $this->assertEquals('Clothing', $result[$category3->id]);

        // Verify data is cached
        $cachedResult = Cache::get('product_categories');
        $this->assertEquals($result, $cachedResult);
    }

    #[Test]
    public function get_all_categories_returns_empty_array_when_no_categories(): void
    {
        $result = $this->service->getAllCategories();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function get_all_categories_orders_by_name(): void
    {
        // Create categories in different order
        ProductCategory::factory()->create(['name' => 'Zebra Category']);
        ProductCategory::factory()->create(['name' => 'Alpha Category']);
        ProductCategory::factory()->create(['name' => 'Beta Category']);

        $result = $this->service->getAllCategories();
        $names = array_values($result);

        $this->assertEquals(['Alpha Category', 'Beta Category', 'Zebra Category'], $names);
    }

    #[Test]
    public function get_all_suppliers_returns_user_specific_results(): void
    {
        Auth::login($this->user);

        // Create suppliers for current user
        $supplier1 = Supplier::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'User Supplier 1'
        ]);
        $supplier2 = Supplier::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'User Supplier 2'
        ]);

        // Create supplier for different user
        $otherUser = User::factory()->create();
        Supplier::factory()->create([
            'user_id' => $otherUser->id,
            'name' => 'Other User Supplier'
        ]);

        $result = $this->service->getAllSuppliers();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertArrayHasKey($supplier1->id, $result);
        $this->assertArrayHasKey($supplier2->id, $result);
        $this->assertEquals('User Supplier 1', $result[$supplier1->id]);
        $this->assertEquals('User Supplier 2', $result[$supplier2->id]);

        // Verify other user's supplier is not included
        $this->assertNotContains('Other User Supplier', $result);
    }

    #[Test]
    public function get_all_suppliers_returns_cached_results(): void
    {
        Auth::login($this->user);

        Supplier::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Test Supplier'
        ]);

        $result = $this->service->getAllSuppliers();

        // Verify data is cached
        $cachedResult = Cache::get('product_suppliers');
        $this->assertEquals($result, $cachedResult);
    }

    #[Test]
    public function get_all_suppliers_orders_by_name(): void
    {
        Auth::login($this->user);

        // Create suppliers in different order
        Supplier::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Zebra Supplier'
        ]);
        Supplier::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Alpha Supplier'
        ]);
        Supplier::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Beta Supplier'
        ]);

        $result = $this->service->getAllSuppliers();
        $names = array_values($result);

        $this->assertEquals(['Alpha Supplier', 'Beta Supplier', 'Zebra Supplier'], $names);
    }

    #[Test]
    public function clear_categories_cache_removes_all_cached_data(): void
    {
        // Create a category and populate cache
        $category = ProductCategory::factory()->create(['slug' => 'electronics', 'name' => 'Electronics']);
        
        // Pre-populate cache with various keys
        Cache::put('product_categories', ['test' => 'data']);
        Cache::put("products_by_category:electronics:0", ['cached' => 'data']);
        Cache::put("products_by_category:electronics:1", ['cached' => 'data']);

        // Verify cache is populated
        $this->assertNotNull(Cache::get('product_categories'));
        $this->assertNotNull(Cache::get("products_by_category:electronics:0"));
        $this->assertNotNull(Cache::get("products_by_category:electronics:1"));

        // Clear cache
        $this->service->clearCategoriesCache();

        // Verify cache is cleared
        $this->assertNull(Cache::get('product_categories'));
        $this->assertNull(Cache::get("products_by_category:electronics:0"));
        $this->assertNull(Cache::get("products_by_category:electronics:1"));
    }

    #[Test]
    public function handle_product_image_returns_old_image_when_no_new_image(): void
    {
        $oldImage = 'products/old-image.jpg';
        
        $result = $this->service->handleProductImage(null, $oldImage);
        
        $this->assertEquals($oldImage, $result);
    }

    #[Test]
    public function handle_product_image_returns_null_when_no_image_provided(): void
    {
        $result = $this->service->handleProductImage(null, null);
        
        $this->assertNull($result);
    }

    #[Test]
    public function handle_product_image_uploads_new_file(): void
    {
        Storage::fake('public');
        
        $file = UploadedFile::fake()->image('test-image.jpg', 800, 600);
        
        $result = $this->service->handleProductImage($file, null);
        
        $this->assertNotNull($result);
        $this->assertStringStartsWith('products/', $result);
        $this->assertStringEndsWith('.jpg', $result);
        
        // Verify file was stored
        $filename = basename($result);
        Storage::disk('public')->assertExists('products/' . $filename);
    }

    #[Test]
    public function handle_product_image_deletes_old_image_when_uploading_new(): void
    {
        Storage::fake('public');
        
        // Create an old image file
        $oldImagePath = 'products/old-image.jpg';
        Storage::disk('public')->put($oldImagePath, 'fake image content');
        Storage::disk('public')->assertExists($oldImagePath);
        
        $newFile = UploadedFile::fake()->image('new-image.jpg', 800, 600);
        
        $result = $this->service->handleProductImage($newFile, $oldImagePath);
        
        $this->assertNotNull($result);
        $this->assertNotEquals($oldImagePath, $result);
        
        // Verify old image was deleted
        Storage::disk('public')->assertMissing($oldImagePath);
        
        // Verify new image was stored
        $filename = basename($result);
        Storage::disk('public')->assertExists('products/' . $filename);
    }

    #[Test]
    public function handle_product_image_deletes_old_image_when_removing(): void
    {
        Storage::fake('public');
        
        // Create an old image file
        $oldImagePath = 'products/old-image.jpg';
        Storage::disk('public')->put($oldImagePath, 'fake image content');
        Storage::disk('public')->assertExists($oldImagePath);
        
        // Simulate image removal request
        request()->merge(['image_remove' => '1']);
        
        $result = $this->service->handleProductImage(null, $oldImagePath);
        
        $this->assertNull($result);
        
        // Verify old image was deleted
        Storage::disk('public')->assertMissing($oldImagePath);
    }

    #[Test]
    public function handle_product_image_generates_filename_correctly(): void
    {
        Storage::fake('public');
        
        $file1 = UploadedFile::fake()->image('test1.jpg', 800, 600);
        
        // Add a small delay to ensure different timestamps if needed
        usleep(1000); // 1 millisecond
        
        $file2 = UploadedFile::fake()->image('test2.jpg', 800, 600);
        
        $result1 = $this->service->handleProductImage($file1, null);
        $result2 = $this->service->handleProductImage($file2, null);
        
        $this->assertNotNull($result1);
        $this->assertNotNull($result2);
        
        // Extract filenames
        $filename1 = basename($result1);
        $filename2 = basename($result2);
        
        // Both files should exist on storage
        Storage::disk('public')->assertExists('products/' . $filename1);
        Storage::disk('public')->assertExists('products/' . $filename2);
        
        // Verify both are valid image paths
        $this->assertStringStartsWith('products/', $result1);
        $this->assertStringStartsWith('products/', $result2);
        $this->assertStringEndsWith('.jpg', $result1);
        $this->assertStringEndsWith('.jpg', $result2);
        
        // With different original names, filenames should be different
        $this->assertNotEquals($filename1, $filename2);
    }

    #[Test]
    public function service_handles_empty_category_cache_gracefully(): void
    {
        // Test clearing cache when no categories exist
        $this->service->clearCategoriesCache();
        
        // Should not throw any exceptions
        $this->assertTrue(true);
    }
}
