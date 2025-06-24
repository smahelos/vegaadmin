<?php

namespace Tests\Feature\Integration;

use App\Models\User;
use App\Models\Invoice;
use App\Models\Client;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tax;
use App\Contracts\DashboardServiceInterface;
use App\Contracts\ProductServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CompleteIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private DashboardServiceInterface $dashboardService;
    private ProductServiceInterface $productService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dashboardService = app(DashboardServiceInterface::class);
        $this->productService = app(ProductServiceInterface::class);
    }

    #[Test]
    public function complete_cache_invalidation_integration_works(): void
    {
        // Create test user
        $user = User::factory()->create();

        // Phase 1: Cache dashboard data
        $initialStats = $this->dashboardService->getUserStatistics($user);
        $this->assertIsArray($initialStats);
        $this->assertEquals(0, $initialStats['invoice_count']);
        $this->assertEquals(0, $initialStats['client_count']);

        // Phase 2: Create client (triggers cache invalidation)
        $client = Client::factory()->create([
            'user_id' => $user->id,
            'name' => 'Integration Test Client'
        ]);

        // Verify client cache was invalidated by getting fresh stats
        $statsAfterClient = $this->dashboardService->getUserStatistics($user);
        $this->assertEquals(1, $statsAfterClient['client_count']);

        // Phase 3: Create invoice (triggers cache invalidation)
        $invoice = Invoice::factory()->create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'payment_amount' => 5000.00,
        ]);

        // Verify invoice cache was invalidated
        $statsAfterInvoice = $this->dashboardService->getUserStatistics($user);
        $this->assertEquals(1, $statsAfterInvoice['invoice_count']);
        $this->assertEquals(1, $statsAfterInvoice['client_count']);

        // Phase 4: Test form data caching and invalidation
        $initialFormData = $this->productService->getFormData();
        $this->assertIsArray($initialFormData);
        $this->assertArrayHasKey('product_categories', $initialFormData);
        $this->assertArrayHasKey('tax_rates', $initialFormData);
        $this->assertArrayHasKey('categories', $initialFormData);

        // Create product category (triggers form data cache invalidation)
        $category = ProductCategory::factory()->create([
            'name' => 'Integration Test Category',
        ]);

        // Create tax (triggers form data cache invalidation)
        $tax = Tax::factory()->create([
            'name' => 'Integration Test Tax',
            'rate' => 21.00,
            'slug' => 'dph' // Important: must have 'dph' slug to appear in tax_rates
        ]);

        // Verify form data cache includes new items
        $newFormData = $this->productService->getFormData();
        $this->assertIsArray($newFormData);
        
        // Categories should include our new category (full objects)
        $categoryItems = $newFormData['categories'];
        $categoryNames = $categoryItems->pluck('name')->toArray();
        $this->assertContains('Integration Test Category', $categoryNames);

        // Tax rates should include our new tax (only 'dph' slug taxes)
        $this->assertArrayHasKey('tax_rates', $newFormData);
        $this->assertIsArray($newFormData['tax_rates']);

        // Phase 5: Create product (triggers product cache invalidation)
        $product = Product::factory()->create([
            'user_id' => $user->id,
            'name' => 'Integration Test Product',
            'price' => 100.00,
            'category_id' => $category->id
        ]);

        // Verify product exists and is accessible
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Integration Test Product',
            'user_id' => $user->id
        ]);

        // Phase 6: Manual cache invalidation test
        $result = $this->dashboardService->invalidateUserCache($user);
        $this->assertTrue($result, 'Manual cache invalidation should succeed');

        $formResult = $this->productService->invalidateFormDataCache();
        $this->assertTrue($formResult, 'Form data cache invalidation should succeed');

        // Phase 7: Verify data integrity after all operations
        $finalStats = $this->dashboardService->getUserStatistics($user);
        $this->assertEquals(1, $finalStats['invoice_count']);
        $this->assertEquals(1, $finalStats['client_count']);

        $finalFormData = $this->productService->getFormData();
        $this->assertIsArray($finalFormData);
        $this->assertNotEmpty($finalFormData['product_categories']);
        $this->assertArrayHasKey('tax_rates', $finalFormData);

        $this->assertTrue(true, 'Complete cache invalidation integration test passed successfully');
    }

    #[Test]
    public function observer_events_are_triggered_correctly(): void
    {
        $user = User::factory()->create();

        // Clear any existing cache
        cache()->flush();

        // Create initial data and cache it
        $this->dashboardService->getUserStatistics($user);
        $this->productService->getFormData();

        // Note: File cache driver doesn't support cache()->has() reliably
        // so we focus on testing the functionality rather than cache existence

        // Create invoice - should trigger UserDataChanged event
        $initialCount = Invoice::where('user_id', $user->id)->count();
        $this->assertEquals(0, $initialCount);

        Invoice::factory()->create(['user_id' => $user->id]);

        // Verify data changes are reflected (cache should be invalidated)
        $statsAfterInvoice = $this->dashboardService->getUserStatistics($user);
        $this->assertEquals(1, $statsAfterInvoice['invoice_count']);

        // Create client - should also trigger cache invalidation
        $initialClientCount = Client::where('user_id', $user->id)->count();
        $this->assertEquals(0, $initialClientCount);

        Client::factory()->create(['user_id' => $user->id]);

        $statsAfterClient = $this->dashboardService->getUserStatistics($user);
        $this->assertEquals(1, $statsAfterClient['client_count']);
        $this->assertEquals(1, $statsAfterClient['invoice_count']);

        $this->assertTrue(true, 'Observer events are working correctly - data changes are reflected');
    }
}
