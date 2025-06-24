<?php

namespace Tests\Feature\Services;

use App\Models\StatusCategory;
use App\Services\StatusService;
use App\Contracts\StatusServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StatusServiceFeatureTest extends TestCase
{
    use RefreshDatabase;

    private StatusService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new StatusService();
    }

    #[Test]
    public function get_all_categories_returns_ordered_categories_as_array(): void
    {
        // Arrange
        $category1 = StatusCategory::factory()->create(['name' => 'Zebra Category']);
        $category2 = StatusCategory::factory()->create(['name' => 'Alpha Category']);
        $category3 = StatusCategory::factory()->create(['name' => 'Beta Category']);

        // Act
        $result = $this->service->getAllCategories();

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        
        // Should be ordered by name
        $keys = array_keys($result);
        $this->assertEquals($category2->id, $keys[0]); // Alpha Category
        $this->assertEquals($category3->id, $keys[1]); // Beta Category  
        $this->assertEquals($category1->id, $keys[2]); // Zebra Category
        
        $this->assertEquals('Alpha Category', $result[$category2->id]);
        $this->assertEquals('Beta Category', $result[$category3->id]);
        $this->assertEquals('Zebra Category', $result[$category1->id]);
    }

    #[Test]
    public function get_all_categories_returns_empty_array_when_no_categories(): void
    {
        // Act
        $result = $this->service->getAllCategories();

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function get_all_categories_uses_cache(): void
    {
        // Arrange
        $category = StatusCategory::factory()->create(['name' => 'Test Category']);
        
        // Clear any existing cache
        Cache::forget('status_categories');

        // Act - First call should cache the result
        $result1 = $this->service->getAllCategories();
        
        // Delete the category from database
        $category->delete();
        
        // Act - Second call should return cached result
        $result2 = $this->service->getAllCategories();

        // Assert
        $this->assertEquals($result1, $result2);
        $this->assertArrayHasKey($category->id, $result2);
        $this->assertEquals('Test Category', $result2[$category->id]);
    }

    #[Test]
    public function get_all_categories_cache_expires_after_5_minutes(): void
    {
        // Arrange
        $category = StatusCategory::factory()->create(['name' => 'Test Category']);

        // Act - First call to populate cache
        $this->service->getAllCategories();

        // Assert cache exists
        $this->assertTrue(Cache::has('status_categories'));

        // Simulate time passing (cache should still exist within 5 minutes)
        $this->travel(4)->minutes();
        $this->assertTrue(Cache::has('status_categories'));

        // Simulate cache expiration (after 5+ minutes)
        $this->travel(2)->minutes(); // Total 6 minutes
        $this->assertFalse(Cache::has('status_categories'));
    }

    #[Test]
    public function clear_categories_cache_removes_main_cache(): void
    {
        // Arrange
        $category = StatusCategory::factory()->create(['name' => 'Test Category']);
        
        // Populate cache
        $this->service->getAllCategories();
        $this->assertTrue(Cache::has('status_categories'));

        // Act
        $this->service->clearCategoriesCache();

        // Assert
        $this->assertFalse(Cache::has('status_categories'));
    }

    #[Test]
    public function clear_categories_cache_removes_slug_based_caches(): void
    {
        // Arrange
        $category1 = StatusCategory::factory()->create(['slug' => 'active-status']);
        $category2 = StatusCategory::factory()->create(['slug' => 'inactive-status']);

        // Set up some cache keys that should be cleared
        Cache::put('statuses_by_category:active-status:0', ['data'], 300);
        Cache::put('statuses_by_category:active-status:1', ['data'], 300);
        Cache::put('statuses_by_category:inactive-status:0', ['data'], 300);
        Cache::put('statuses_by_category:inactive-status:1', ['data'], 300);
        Cache::put('status_categories', ['data'], 300);

        // Verify cache exists
        $this->assertTrue(Cache::has('statuses_by_category:active-status:0'));
        $this->assertTrue(Cache::has('statuses_by_category:active-status:1'));
        $this->assertTrue(Cache::has('statuses_by_category:inactive-status:0'));
        $this->assertTrue(Cache::has('statuses_by_category:inactive-status:1'));
        $this->assertTrue(Cache::has('status_categories'));

        // Act
        $this->service->clearCategoriesCache();

        // Assert
        $this->assertFalse(Cache::has('statuses_by_category:active-status:0'));
        $this->assertFalse(Cache::has('statuses_by_category:active-status:1'));
        $this->assertFalse(Cache::has('statuses_by_category:inactive-status:0'));
        $this->assertFalse(Cache::has('statuses_by_category:inactive-status:1'));
        $this->assertFalse(Cache::has('status_categories'));
    }

    #[Test]
    public function clear_categories_cache_handles_no_categories(): void
    {
        // Arrange - No categories in database
        Cache::put('status_categories', ['some' => 'data'], 300);
        $this->assertTrue(Cache::has('status_categories'));

        // Act
        $this->service->clearCategoriesCache();

        // Assert - Should clear main cache without errors
        $this->assertFalse(Cache::has('status_categories'));
    }

    #[Test]
    public function get_all_categories_maintains_data_integrity_across_calls(): void
    {
        // Arrange
        $category1 = StatusCategory::factory()->create(['name' => 'Category One']);
        $category2 = StatusCategory::factory()->create(['name' => 'Category Two']);

        // Act
        $result1 = $this->service->getAllCategories();
        $result2 = $this->service->getAllCategories();

        // Assert
        $this->assertEquals($result1, $result2);
        $this->assertCount(2, $result1);
        $this->assertCount(2, $result2);
    }
}
