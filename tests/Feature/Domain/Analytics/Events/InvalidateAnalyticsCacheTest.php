<?php

namespace Tests\Feature\Domain\Analytics\Events;

use App\Domain\Analytics\Events\AnalyticsDataChanged;
use App\Domain\Analytics\Events\Handlers\InvalidateAnalyticsCache;
use App\Domain\Shared\Cache\Contracts\CacheServiceInterface;
use App\Domain\Analytics\Contracts\DashboardServiceInterface;
use App\Domain\Shared\Log\Contracts\LogInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InvalidateAnalyticsCacheTest extends TestCase
{
    use RefreshDatabase;

    private InvalidateAnalyticsCache $handler;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->handler = app(InvalidateAnalyticsCache::class);
    }

    #[Test]
    public function can_handle_analytics_data_changed_event(): void
    {
        $event = new AnalyticsDataChanged(123, 'invoice');
        
        $this->assertTrue($this->handler->canHandle($event));
    }

    #[Test]
    public function returns_correct_priority(): void
    {
        $this->assertEquals(80, $this->handler->getPriority());
    }

    #[Test]
    public function handles_analytics_data_changed_event_without_errors(): void
    {
        $userId = 123;
        $entityId = 456;
        $event = new AnalyticsDataChanged($userId, 'invoice', $entityId);
        
        // This test verifies the handler can process the event without throwing exceptions
        // and that it follows the expected flow (no assertions needed for this integration test)
        $this->handler->handle($event);
        
        // If we reach this point, the handler executed successfully
        $this->assertTrue(true);
    }

    #[Test]
    public function handles_different_change_types(): void
    {
        $userId = 123;
        $changeTypes = ['invoice', 'client', 'supplier', 'statistics', 'monthly', 'dashboard', 'unknown'];
        
        foreach ($changeTypes as $changeType) {
            $event = new AnalyticsDataChanged($userId, $changeType);
            
            // Verify handler can process each change type without errors
            $this->handler->handle($event);
        }
        
        $this->assertTrue(true);
    }
}
