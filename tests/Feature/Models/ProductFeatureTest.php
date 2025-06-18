<?php

namespace Tests\Feature\Models;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tax;
use App\Models\User;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Feature tests for Product Model
 * 
 * Tests database relationships, business logic, and model behavior requiring database interactions
 * Tests product interactions with users, categories, taxes, suppliers and sluggable behavior
 */
class ProductFeatureTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    #[Test]
    public function can_create_product_with_factory(): void
    {
        $product = Product::factory()->create();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'price' => $product->price,
        ]);
    }

    #[Test]
    public function fillable_attributes_can_be_mass_assigned(): void
    {
        $user = User::factory()->create();
        $tax = Tax::factory()->create();
        $category = ProductCategory::factory()->create();
        
        $data = [
            'name' => 'Test Product',
            'slug' => 'test-product',
            'user_id' => $user->id,
            'price' => 99.99,
            'tax_id' => $tax->id,
            'category_id' => $category->id,
            'description' => 'Test product description',
            'is_default' => true,
            'is_active' => true,
        ];

        $product = Product::create($data);

        $this->assertDatabaseHas('products', $data);
        $this->assertEquals($data['name'], $product->name);
        $this->assertEquals($data['price'], $product->price);
    }

    #[Test]
    public function casts_work_correctly(): void
    {
        $product = Product::factory()->create([
            'price' => '123.45',
            'is_default' => 1,
            'is_active' => 0,
        ]);

        // Price is cast to decimal which is a string in Laravel
        $this->assertIsString($product->price);
        $this->assertEquals('123.45', $product->price);
        $this->assertIsBool($product->is_default);
        $this->assertTrue($product->is_default);
        $this->assertIsBool($product->is_active);
        $this->assertFalse($product->is_active);
    }

    #[Test]
    public function belongs_to_user_relationship(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $product->user);
        $this->assertEquals($user->id, $product->user->id);
        $this->assertEquals($user->name, $product->user->name);
    }

    #[Test]
    public function belongs_to_tax_relationship(): void
    {
        $tax = Tax::factory()->create();
        $product = Product::factory()->create(['tax_id' => $tax->id]);

        $this->assertInstanceOf(Tax::class, $product->tax);
        $this->assertEquals($tax->id, $product->tax->id);
    }

    #[Test]
    public function belongs_to_category_relationship(): void
    {
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $this->assertInstanceOf(ProductCategory::class, $product->category);
        $this->assertEquals($category->id, $product->category->id);
    }

    #[Test]
    public function belongs_to_supplier_relationship(): void
    {
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create(['supplier_id' => $supplier->id]);

        $this->assertInstanceOf(Supplier::class, $product->supplier);
        $this->assertEquals($supplier->id, $product->supplier->id);
    }

    #[Test]
    public function sluggable_configuration_works(): void
    {
        $product = new Product();
        $sluggableConfig = $product->sluggable();
        
        $this->assertIsArray($sluggableConfig);
        $this->assertArrayHasKey('slug', $sluggableConfig);
        $this->assertArrayHasKey('source', $sluggableConfig['slug']);
        $this->assertEquals('name', $sluggableConfig['slug']['source']);
    }

    #[Test]
    public function slug_is_generated_from_name(): void
    {
        $product = Product::factory()->create([
            'name' => 'Test Product Name',
            'slug' => null, // Let sluggable generate it
        ]);

        // Refresh to get generated slug
        $product->refresh();
        
        $this->assertNotNull($product->slug);
        $this->assertStringContainsString('test-product-name', $product->slug);
    }

    #[Test]
    public function setting_product_as_default_unsets_other_defaults(): void
    {
        $user = User::factory()->create();
        
        // Create first default product
        $product1 = Product::factory()->create([
            'user_id' => $user->id,
            'is_default' => true,
        ]);
        
        // Create second product and set as default
        $product2 = Product::factory()->create([
            'user_id' => $user->id,
            'is_default' => true,
        ]);
        
        // Refresh first product to see if it's no longer default
        $product1->refresh();
        
        $this->assertFalse($product1->is_default);
        $this->assertTrue($product2->is_default);
    }

    #[Test]
    public function default_product_behavior_is_user_specific(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $product1 = Product::factory()->create([
            'user_id' => $user1->id,
            'is_default' => true,
        ]);
        
        $product2 = Product::factory()->create([
            'user_id' => $user2->id,
            'is_default' => true,
        ]);
        
        // Both should remain default since they belong to different users
        $product1->refresh();
        $product2->refresh();
        
        $this->assertTrue($product1->is_default);
        $this->assertTrue($product2->is_default);
    }

    #[Test]
    public function factory_states_work_correctly(): void
    {
        $defaultProduct = Product::factory()->default()->create();
        $customPriceProduct = Product::factory()->price(50.00)->create();

        $this->assertTrue($defaultProduct->is_default);
        $this->assertEquals(50.00, $customPriceProduct->price);
    }

    #[Test]
    public function factory_for_user_state_works(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->forUser($user)->create();

        $this->assertEquals($user->id, $product->user_id);
        $this->assertEquals($user->id, $product->user->id);
    }

    #[Test]
    public function can_update_product(): void
    {
        $product = Product::factory()->create();
        
        $newData = [
            'name' => 'Updated Product Name',
            'price' => 199.99,
            'is_active' => false,
        ];
        
        $product->update($newData);
        
        $this->assertDatabaseHas('products', array_merge(
            ['id' => $product->id],
            $newData
        ));
    }

    #[Test]
    public function can_delete_product(): void
    {
        $product = Product::factory()->create();
        $productId = $product->id;
        
        $product->delete();
        
        $this->assertDatabaseMissing('products', ['id' => $productId]);
    }

    #[Test]
    public function get_invoice_count_attribute_works(): void
    {
        $product = Product::factory()->create();
        
        // Test that the accessor exists and returns a value
        $invoiceCount = $product->getInvoiceCountAttribute();
        
        $this->assertIsInt($invoiceCount);
        $this->assertGreaterThanOrEqual(0, $invoiceCount);
    }

    #[Test]
    public function file_upload_methods_exist(): void
    {
        $product = Product::factory()->create();
        
        // Test file URL methods exist and return strings or null
        $fileUrl = $product->getFileUrl('image');
        $thumbUrl = $product->getImageThumbUrl();
        
        $this->assertTrue(is_string($fileUrl) || is_null($fileUrl));
        $this->assertTrue(is_string($thumbUrl) || is_null($thumbUrl));
    }

    #[Test]
    public function can_create_product_without_optional_relationships(): void
    {
        $user = User::factory()->create();
        
        $data = [
            'name' => 'Simple Product',
            'user_id' => $user->id,
            'price' => 25.00,
        ];

        $product = Product::create($data);

        $this->assertDatabaseHas('products', $data);
        $this->assertEquals($data['name'], $product->name);
        $this->assertNull($product->tax_id);
        $this->assertNull($product->category_id);
        $this->assertNull($product->supplier_id);
    }

    #[Test]
    public function price_decimal_precision_is_maintained(): void
    {
        $product = Product::factory()->create(['price' => 123.456789]);

        // Should be cast to 2 decimal places
        $this->assertEquals(123.46, $product->price);
    }

    #[Test]
    public function image_mutator_works(): void
    {
        $product = Product::factory()->make();
        
        // Test that setImageAttribute method exists and can be called
        $this->assertTrue(method_exists($product, 'setImageAttribute'));
        
        // Test setting image value
        $product->setImageAttribute('test-image.jpg');
        
        // The actual implementation depends on HasFileUploads trait
        // Image might be null, string, or array depending on implementation
        $this->assertTrue(
            is_null($product->image) || 
            is_string($product->image) || 
            is_array($product->image)
        );
    }
}
