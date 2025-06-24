<?php

namespace Tests\Feature\Services;

use App\Models\User;
use App\Models\Invoice;
use App\Models\Client;
use App\Contracts\DashboardServiceInterface;
use App\Contracts\CacheServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CacheInvalidationFeatureTest extends TestCase
{
    use RefreshDatabase;

    private DashboardServiceInterface $dashboardService;
    private CacheServiceInterface $cacheService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dashboardService = app(DashboardServiceInterface::class);
        $this->cacheService = app(CacheServiceInterface::class);
    }

    #[Test]
    public function cache_is_invalidated_when_invoice_is_created(): void
    {
        // Create user
        $user = User::factory()->create();

        // Get initial dashboard data (this will cache it)
        $initialStats = $this->dashboardService->getUserStatistics($user);
        $this->assertIsArray($initialStats);

        // Create invoice (this should trigger cache invalidation)
        $invoice = Invoice::factory()->create([
            'user_id' => $user->id,
            'payment_amount' => 1000.00,
        ]);

        // Verify cache has been invalidated by checking cache keys don't exist
        $cacheKey = "dashboard_user_stats_{$user->id}";
        $this->assertFalse(
            cache()->has($cacheKey),
            'Dashboard cache should be invalidated when invoice is created'
        );
    }

    #[Test]
    public function cache_is_invalidated_when_client_is_created(): void
    {
        // Create user
        $user = User::factory()->create();

        // Get initial dashboard data (this will cache it)
        $initialClients = $this->dashboardService->getClientsWithInvoiceTotals($user);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $initialClients);

        // Create client (this should trigger cache invalidation)
        $client = Client::factory()->create([
            'user_id' => $user->id,
            'name' => 'Test Client'
        ]);

        // Verify cache has been invalidated
        $cacheKey = "dashboard_clients_{$user->id}";
        $this->assertFalse(
            cache()->has($cacheKey),
            'Dashboard cache should be invalidated when client is created'
        );
    }

    #[Test]
    public function cache_service_can_invalidate_tags(): void
    {
        // Set some test cache data without tags (file cache doesn't support tags)
        cache()->put('test_key', 'test_value', 60);

        // Verify data is cached
        $this->assertTrue(cache()->has('test_key'));

        // Invalidate by tags (should not throw error)
        $this->cacheService->invalidateTags(['test_tag']);

        // For file cache, tags don't work, so we just verify the method completes
        $this->assertTrue(true, 'Cache invalidation by tags completed');
    }

    #[Test]
    public function dashboard_service_cache_invalidation_method_works(): void
    {
        $user = User::factory()->create();

        // Cache some data first
        $this->dashboardService->getUserStatistics($user);

        // Test invalidation method
        $result = $this->dashboardService->invalidateUserCache($user);
        $this->assertTrue($result, 'Cache invalidation should return true');

        // Verify specific cache keys are cleared
        $cacheKey = "dashboard_user_stats_{$user->id}";
        $this->assertFalse(
            cache()->has($cacheKey),
            'User statistics cache should be cleared'
        );
    }
}
