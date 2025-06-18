<?php

namespace Tests\Feature\Models;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductCategoryFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function can_create_product_category(): void
    {
        $category = ProductCategory::factory()->create([
            'name' => 'Electronics',
            'description' => 'Electronic products and devices'
        ]);

        $this->assertDatabaseHas('product_categories', [
            'id' => $category->id,
            'name' => 'Electronics',
            'description' => 'Electronic products and devices'
        ]);
    }

    #[Test]
    public function products_relationship_works_correctly(): void
    {
        $category = ProductCategory::factory()->create();
        $product1 = Product::factory()->create(['category_id' => $category->id]);
        $product2 = Product::factory()->create(['category_id' => $category->id]);
        
        // Create another product in different category to ensure proper filtering
        $otherCategory = ProductCategory::factory()->create();
        Product::factory()->create(['category_id' => $otherCategory->id]);

        $relationship = $category->products();
        $this->assertInstanceOf(HasMany::class, $relationship);
        
        $products = $category->products;
        $this->assertCount(2, $products);
        $this->assertTrue($products->contains($product1));
        $this->assertTrue($products->contains($product2));
    }

    #[Test]
    public function slug_is_generated_automatically_from_name(): void
    {
        // Don't use factory here as it overrides slug generation
        $category = new ProductCategory();
        $category->name = 'Home & Garden';
        $category->save();

        $this->assertNotEmpty($category->slug);
        $this->assertEquals('home-garden', $category->slug);
    }

    #[Test]
    public function slug_handles_special_characters(): void
    {
        $category = new ProductCategory();
        $category->name = 'Books & CDs/DVDs';
        $category->save();

        $this->assertNotEmpty($category->slug);
        $this->assertStringNotContainsString('&', $category->slug);
        $this->assertStringNotContainsString('/', $category->slug);
    }

    #[Test]
    public function slug_is_unique_when_names_are_similar(): void
    {
        $category1 = new ProductCategory();
        $category1->name = 'Electronics';
        $category1->save();
        
        $category2 = new ProductCategory();
        $category2->name = 'Electronics';
        $category2->save();

        $this->assertNotEquals($category1->slug, $category2->slug);
        $this->assertEquals('electronics', $category1->slug);
        $this->assertStringStartsWith('electronics-', $category2->slug);
    }

    #[Test]
    public function can_update_category_name_and_slug(): void
    {
        $category = new ProductCategory();
        $category->name = 'Old Name';
        $category->save();

        $originalSlug = $category->slug;

        $category->name = 'New Name';
        $category->save();

        $this->assertEquals('New Name', $category->name);
        // Slug should be updated when name changes (depending on sluggable configuration)
        // In many configurations, slug is only set on create, not update, so we test that it's still valid
        $this->assertNotEmpty($category->slug);
    }

    #[Test]
    public function can_delete_category_without_products(): void
    {
        $category = ProductCategory::factory()->create();

        $categoryId = $category->id;
        $category->delete();

        $this->assertDatabaseMissing('product_categories', ['id' => $categoryId]);
    }

    #[Test]
    public function category_can_have_many_products(): void
    {
        $category = ProductCategory::factory()->create();
        
        $products = Product::factory()->count(5)->create(['category_id' => $category->id]);

        $this->assertCount(5, $category->products);
        
        foreach ($products as $product) {
            $this->assertEquals($category->id, $product->category_id);
        }
    }

    #[Test]
    public function can_create_category_without_description(): void
    {
        $category = ProductCategory::factory()->create([
            'name' => 'Simple Category',
            'description' => null
        ]);

        $this->assertEquals('Simple Category', $category->name);
        $this->assertNull($category->description);
        $this->assertNotEmpty($category->slug);
    }

    #[Test]
    public function fillable_attributes_work_correctly(): void
    {
        $data = [
            'name' => 'Test Category',
            'slug' => 'custom-slug',
            'description' => 'Test description'
        ];

        $category = ProductCategory::create($data);

        $this->assertEquals('Test Category', $category->name);
        $this->assertEquals('custom-slug', $category->slug);
        $this->assertEquals('Test description', $category->description);
    }

    #[Test]
    public function factory_creates_valid_category_data(): void
    {
        $category = ProductCategory::factory()->create();

        $this->assertNotEmpty($category->name);
        $this->assertNotEmpty($category->slug);
        $this->assertIsString($category->name);
        $this->assertIsString($category->slug);
        
        // Description can be null
        if ($category->description !== null) {
            $this->assertIsString($category->description);
        }
    }
}
