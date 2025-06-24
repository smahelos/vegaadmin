<?php

namespace Tests\Feature\Services;

use App\Models\Tax;
use App\Services\TaxesService;
use App\Contracts\TaxesServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaxesServiceFeatureTest extends TestCase
{
    use RefreshDatabase;

    private TaxesService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TaxesService();
    }

    #[Test]
    public function get_all_taxes_returns_ordered_taxes_as_slug_name_array(): void
    {
        // Arrange
        $tax1 = Tax::factory()->create(['name' => 'Zebra Tax', 'slug' => 'zebra-tax']);
        $tax2 = Tax::factory()->create(['name' => 'Alpha Tax', 'slug' => 'alpha-tax']);
        $tax3 = Tax::factory()->create(['name' => 'Beta Tax', 'slug' => 'beta-tax']);

        // Act
        $result = $this->service->getAllTaxes();

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        
        // Should be ordered by name and use slug as key
        $expected = [
            'alpha-tax' => 'Alpha Tax',
            'beta-tax' => 'Beta Tax',
            'zebra-tax' => 'Zebra Tax'
        ];
        
        $this->assertEquals($expected, $result);
    }

    #[Test]
    public function get_all_taxes_for_select_returns_ordered_taxes_as_id_name_array(): void
    {
        // Arrange
        $tax1 = Tax::factory()->create(['name' => 'Zebra Tax']);
        $tax2 = Tax::factory()->create(['name' => 'Alpha Tax']);
        $tax3 = Tax::factory()->create(['name' => 'Beta Tax']);

        // Act
        $result = $this->service->getAllTaxesForSelect();

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        
        // Should be ordered by name and use id as key
        $this->assertEquals('Alpha Tax', $result[$tax2->id]);
        $this->assertEquals('Beta Tax', $result[$tax3->id]);
        $this->assertEquals('Zebra Tax', $result[$tax1->id]);
        
        // Verify keys are IDs not slugs
        $this->assertArrayHasKey($tax1->id, $result);
        $this->assertArrayHasKey($tax2->id, $result);
        $this->assertArrayHasKey($tax3->id, $result);
    }

    #[Test]
    public function get_all_taxes_returns_empty_array_when_no_taxes(): void
    {
        // Act
        $result = $this->service->getAllTaxes();

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function get_all_taxes_for_select_returns_empty_array_when_no_taxes(): void
    {
        // Act
        $result = $this->service->getAllTaxesForSelect();

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function get_all_taxes_uses_cache(): void
    {
        // Arrange
        $tax = Tax::factory()->create(['name' => 'Test Tax', 'slug' => 'test-tax']);
        
        // Clear any existing cache
        Cache::forget('taxes');

        // Act - First call should cache the result
        $result1 = $this->service->getAllTaxes();
        
        // Delete the tax from database
        $tax->delete();
        
        // Act - Second call should return cached result
        $result2 = $this->service->getAllTaxes();

        // Assert
        $this->assertEquals($result1, $result2);
        $this->assertArrayHasKey('test-tax', $result2);
        $this->assertEquals('Test Tax', $result2['test-tax']);
    }

    #[Test]
    public function get_all_taxes_for_select_uses_cache(): void
    {
        // Arrange
        $tax = Tax::factory()->create(['name' => 'Test Tax']);
        
        // Clear any existing cache
        Cache::forget('taxes_for_select');

        // Act - First call should cache the result
        $result1 = $this->service->getAllTaxesForSelect();
        
        // Delete the tax from database
        $tax->delete();
        
        // Act - Second call should return cached result
        $result2 = $this->service->getAllTaxesForSelect();

        // Assert
        $this->assertEquals($result1, $result2);
        $this->assertArrayHasKey($tax->id, $result2);
        $this->assertEquals('Test Tax', $result2[$tax->id]);
    }

    #[Test]
    public function taxes_cache_expires_after_5_minutes(): void
    {
        // Arrange
        Tax::factory()->create(['name' => 'Test Tax']);

        // Act - First call to populate cache
        $this->service->getAllTaxes();

        // Assert cache exists
        $this->assertTrue(Cache::has('taxes'));

        // Simulate time passing (cache should still exist within 5 minutes)
        $this->travel(4)->minutes();
        $this->assertTrue(Cache::has('taxes'));

        // Simulate cache expiration (after 5+ minutes)
        $this->travel(2)->minutes(); // Total 6 minutes
        $this->assertFalse(Cache::has('taxes'));
    }

    #[Test]
    public function taxes_for_select_cache_expires_after_5_minutes(): void
    {
        // Arrange
        Tax::factory()->create(['name' => 'Test Tax']);

        // Act - First call to populate cache
        $this->service->getAllTaxesForSelect();

        // Assert cache exists
        $this->assertTrue(Cache::has('taxes_for_select'));

        // Simulate time passing (cache should still exist within 5 minutes)
        $this->travel(4)->minutes();
        $this->assertTrue(Cache::has('taxes_for_select'));

        // Simulate cache expiration (after 5+ minutes)
        $this->travel(2)->minutes(); // Total 6 minutes
        $this->assertFalse(Cache::has('taxes_for_select'));
    }

    #[Test]
    public function clear_categories_cache_removes_all_tax_caches(): void
    {
        // Arrange
        $tax = Tax::factory()->create(['name' => 'Test Tax']);
        
        // Populate both caches
        $this->service->getAllTaxes();
        $this->service->getAllTaxesForSelect();
        
        $this->assertTrue(Cache::has('taxes'));
        $this->assertTrue(Cache::has('taxes_for_select'));

        // Act
        $this->service->clearCategoriesCache();

        // Assert
        $this->assertFalse(Cache::has('taxes'));
        $this->assertFalse(Cache::has('taxes_for_select'));
    }

    #[Test]
    public function clear_categories_cache_handles_no_existing_cache(): void
    {
        // Arrange - No cache exists
        $this->assertFalse(Cache::has('taxes'));
        $this->assertFalse(Cache::has('taxes_for_select'));

        // Act - Should not throw any errors
        $this->service->clearCategoriesCache();

        // Assert - Still no cache
        $this->assertFalse(Cache::has('taxes'));
        $this->assertFalse(Cache::has('taxes_for_select'));
    }

    #[Test]
    public function both_methods_maintain_data_integrity_across_calls(): void
    {
        // Arrange
        $tax1 = Tax::factory()->create(['name' => 'Tax One', 'slug' => 'tax-one']);
        $tax2 = Tax::factory()->create(['name' => 'Tax Two', 'slug' => 'tax-two']);

        // Act
        $allTaxes1 = $this->service->getAllTaxes();
        $allTaxes2 = $this->service->getAllTaxes();
        $selectTaxes1 = $this->service->getAllTaxesForSelect();
        $selectTaxes2 = $this->service->getAllTaxesForSelect();

        // Assert
        $this->assertEquals($allTaxes1, $allTaxes2);
        $this->assertEquals($selectTaxes1, $selectTaxes2);
        $this->assertCount(2, $allTaxes1);
        $this->assertCount(2, $selectTaxes1);
    }

    #[Test]
    public function different_methods_return_different_key_structures(): void
    {
        // Arrange
        $tax = Tax::factory()->create(['name' => 'Test Tax', 'slug' => 'test-tax']);

        // Act
        $bySlug = $this->service->getAllTaxes();
        $byId = $this->service->getAllTaxesForSelect();

        // Assert
        $this->assertArrayHasKey('test-tax', $bySlug);
        $this->assertArrayHasKey($tax->id, $byId);
        $this->assertEquals('Test Tax', $bySlug['test-tax']);
        $this->assertEquals('Test Tax', $byId[$tax->id]);
    }
}
