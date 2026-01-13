<?php

namespace Tests\Feature\Domain\Analytics\Services;

use App\Domain\Analytics\Contracts\DashboardServiceInterface as AnalyticsDashboardServiceInterface;
use App\Domain\User\Events\UserDataChanged;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use App\Domain\User\ValueObjects\UserId;

/**
 * Feature tests verifying cache lifecycle (warm -> stale -> invalidated) for DashboardService.
 */
class DashboardServiceCachingFeatureTest extends TestCase
{
    use RefreshDatabase;

    private AnalyticsDashboardServiceInterface $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AnalyticsDashboardServiceInterface::class);
    }

    #[Test]
    public function invoice_creation_triggers_automatic_cache_invalidation(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $user->id]);
        Invoice::factory()->create(['user_id' => $user->id, 'client_id' => $client->id, 'payment_amount' => 10]);

        // Warm cache
    $statsInitial = $this->service->getUserStatistics(UserId::fromInt($user->id));
        $this->assertSame(1, $statsInitial->invoiceCount);

        // Create another invoice AFTER cache warmed
        Invoice::factory()->create(['user_id' => $user->id, 'client_id' => $client->id, 'payment_amount' => 20]);

        // Second invoice should bump count immediately (observer dispatches UserDataChanged internally)
    $statsAfterSecond = $this->service->getUserStatistics(UserId::fromInt($user->id));
        $this->assertSame(2, $statsAfterSecond->invoiceCount);

        // Third invoice likewise -> immediate 3
        Invoice::factory()->create(['user_id' => $user->id, 'client_id' => $client->id, 'payment_amount' => 5]);
    $statsAfterThird = $this->service->getUserStatistics(UserId::fromInt($user->id));
        $this->assertSame(3, $statsAfterThird->invoiceCount);

        // Manual dispatch is idempotent (no change in counts)
        app(\Illuminate\Contracts\Events\Dispatcher::class)->dispatch(new UserDataChanged($user->id, 'invoice'));
    $statsAfterManual = $this->service->getUserStatistics(UserId::fromInt($user->id));
        $this->assertSame(3, $statsAfterManual->invoiceCount);
    }

    #[Test]
    public function statistics_are_isolated_per_user_even_with_cache(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $clientA = Client::factory()->create(['user_id' => $userA->id]);
        $clientB = Client::factory()->create(['user_id' => $userB->id]);
        Invoice::factory()->create(['user_id' => $userA->id, 'client_id' => $clientA->id, 'payment_amount' => 50]);

        // Warm cache for user A only
    $statsA1 = $this->service->getUserStatistics(UserId::fromInt($userA->id));
        $this->assertSame(1, $statsA1->invoiceCount);

        // Add invoice for user B (no cache yet) and second for A (stale cache)
        Invoice::factory()->create(['user_id' => $userB->id, 'client_id' => $clientB->id, 'payment_amount' => 30]);
        Invoice::factory()->create(['user_id' => $userA->id, 'client_id' => $clientA->id, 'payment_amount' => 25]);

        // User A was updated -> invoice observer invalidated cache so count now 2
    $statsA2 = $this->service->getUserStatistics(UserId::fromInt($userA->id));
        $this->assertSame(2, $statsA2->invoiceCount);

        // User B first fetch (no prior warm) -> should show 1 directly
    $statsB1 = $this->service->getUserStatistics(UserId::fromInt($userB->id));
        $this->assertSame(1, $statsB1->invoiceCount);

        // Manual dispatch does not change counts further
        app(\Illuminate\Contracts\Events\Dispatcher::class)->dispatch(new UserDataChanged($userA->id, 'invoice'));
    $statsA3 = $this->service->getUserStatistics(UserId::fromInt($userA->id));
        $this->assertSame(2, $statsA3->invoiceCount);

        // User B unaffected (still 1)
    $statsB2 = $this->service->getUserStatistics(UserId::fromInt($userB->id));
        $this->assertSame(1, $statsB2->invoiceCount);
    }
}
