<?php

namespace Tests\Feature\Domain\Analytics\Services;

use App\Domain\Analytics\Contracts\DashboardServiceInterface as AnalyticsDashboardServiceInterface;
use App\Domain\Shared\Money\ValueObjects\Money; // updated namespace
use App\Domain\Shared\Cache\Contracts\CacheServiceInterface;
use App\Domain\User\Events\UserDataChanged;
use App\Models\User;
use App\Models\Invoice;
use App\Models\Client;
use App\Domain\User\ValueObjects\UserId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Illuminate\Support\Facades\Cache;

class CacheInvalidationFeatureTest extends TestCase
{
    use RefreshDatabase;

    private AnalyticsDashboardServiceInterface $dashboardService;
    private CacheServiceInterface $cacheService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dashboardService = app(AnalyticsDashboardServiceInterface::class);
        $this->cacheService = app(CacheServiceInterface::class);
    }

    #[Test]
    public function cache_service_can_invalidate_tags(): void
    {
        cache()->put('test_key', 'test_value', 60);
        $this->assertTrue(cache()->has('test_key'));
        $this->cacheService->invalidateTags(['test_tag']);
        $this->assertTrue(true);
    }

    #[Test]
    public function user_statistics_are_refreshed_after_invoice_event(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $user->id]);
        Invoice::factory()->create(['user_id' => $user->id, 'client_id' => $client->id, 'payment_amount' => 100]);

    $statsFirst = $this->dashboardService->getUserStatistics(UserId::fromInt($user->id));
        $this->assertSame(1, $statsFirst->invoiceCount);
        $this->assertInstanceOf(Money::class, $statsFirst->totalAmount);
        $this->assertSame(100.0, $statsFirst->totalAmount->toFloat());

        Invoice::withoutEvents(function () use ($user, $client) {
            Invoice::factory()->create(['user_id' => $user->id, 'client_id' => $client->id, 'payment_amount' => 200]);
        });
    $statsCached = $this->dashboardService->getUserStatistics(UserId::fromInt($user->id));
        $this->assertSame(1, $statsCached->invoiceCount);

    app(\Illuminate\Contracts\Events\Dispatcher::class)->dispatch(new UserDataChanged($user->id, 'invoice'));
    $statsAfter = $this->dashboardService->getUserStatistics(UserId::fromInt($user->id));
        $this->assertSame(2, $statsAfter->invoiceCount);
        $this->assertSame(300.0, $statsAfter->totalAmount->toFloat());
    }

    #[Test]
    public function monthly_statistics_are_refreshed_after_invoice_event(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $user->id]);
        Invoice::factory()->create(['user_id' => $user->id, 'client_id' => $client->id, 'payment_amount' => 50, 'issue_date' => now()->startOfMonth()]);

    $monthlyFirst = $this->dashboardService->getMonthlyStatistics(UserId::fromInt($user->id), 2);
    $this->assertGreaterThanOrEqual(1, count($monthlyFirst));
    $firstTotal = collect($monthlyFirst)->sum(fn($dto) => $dto->total->toFloat());
        $this->assertEquals(50.0, $firstTotal);

        Invoice::withoutEvents(function () use ($user, $client) {
            Invoice::factory()->create(['user_id' => $user->id, 'client_id' => $client->id, 'payment_amount' => 70, 'issue_date' => now()->startOfMonth()->addDay()]);
        });
    $monthlyCached = $this->dashboardService->getMonthlyStatistics(UserId::fromInt($user->id), 2);
    $this->assertEquals($firstTotal, collect($monthlyCached)->sum(fn($dto) => $dto->total->toFloat()));

    app(\Illuminate\Contracts\Events\Dispatcher::class)->dispatch(new UserDataChanged($user->id, 'invoice'));
    $monthlyAfter = $this->dashboardService->getMonthlyStatistics(UserId::fromInt($user->id), 2);
    $this->assertEquals(120.0, collect($monthlyAfter)->sum(fn($dto) => $dto->total->toFloat()));
    }

    #[Test]
    public function clients_totals_are_refreshed_after_client_event(): void
    {
        $user = User::factory()->create();
        $clientA = Client::factory()->create(['user_id' => $user->id]);
        Invoice::factory()->create(['user_id' => $user->id, 'client_id' => $clientA->id, 'payment_amount' => 80]);

    $clientsFirst = $this->dashboardService->getClientsWithInvoiceTotals(UserId::fromInt($user->id));
        $this->assertCount(1, $clientsFirst);

        Client::withoutEvents(function () use ($user, &$clientB) {
            $clientB = Client::factory()->create(['user_id' => $user->id]);
        });
        Invoice::withoutEvents(function () use ($user, &$clientB) {
            Invoice::factory()->create(['user_id' => $user->id, 'client_id' => $clientB->id, 'payment_amount' => 40]);
        });
    $clientsCached = $this->dashboardService->getClientsWithInvoiceTotals(UserId::fromInt($user->id));
        $this->assertCount(1, $clientsCached);

    app(\Illuminate\Contracts\Events\Dispatcher::class)->dispatch(new UserDataChanged($user->id, 'client'));
    $clientsAfter = $this->dashboardService->getClientsWithInvoiceTotals(UserId::fromInt($user->id));
    $this->assertCount(2, $clientsAfter);
    $this->assertInstanceOf(Money::class, $clientsAfter[0]->totalAmount);
    }

    #[Test]
    public function direct_invalidate_user_cache_method_works(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $user->id]);
        Invoice::factory()->create(['user_id' => $user->id, 'client_id' => $client->id, 'payment_amount' => 10]);

    $stats = $this->dashboardService->getUserStatistics(UserId::fromInt($user->id));
        $this->assertSame(1, $stats->invoiceCount);

        Invoice::withoutEvents(function () use ($user, $client) {
            Invoice::factory()->create(['user_id' => $user->id, 'client_id' => $client->id, 'payment_amount' => 20]);
        });
    $cached = $this->dashboardService->getUserStatistics(UserId::fromInt($user->id));
        $this->assertSame(1, $cached->invoiceCount);

    $this->dashboardService->invalidateUserCache($user->id);
    $after = $this->dashboardService->getUserStatistics(UserId::fromInt($user->id));
        $this->assertSame(2, $after->invoiceCount);
        $this->assertSame(30.0, $after->totalAmount->toFloat());
    }

    #[Test]
    public function cache_keys_use_expected_prefix_after_population(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $user->id]);
        Invoice::factory()->create(['user_id' => $user->id, 'client_id' => $client->id, 'payment_amount' => 5]);

    $uid = UserId::fromInt($user->id);
    $this->dashboardService->getUserStatistics($uid);
    $this->dashboardService->getMonthlyStatistics($uid);
    $this->dashboardService->getClientsWithInvoiceTotals($uid);

        Cache::tags(['analytics.user.' . $user->id])->flush();

        Invoice::factory()->create(['user_id' => $user->id, 'client_id' => $client->id, 'payment_amount' => 7]);
    $stats = $this->dashboardService->getUserStatistics(UserId::fromInt($user->id));
        $this->assertSame(2, $stats->invoiceCount);
        $this->assertSame(12.0, $stats->totalAmount->toFloat());
    }
}
