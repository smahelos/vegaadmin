<?php

namespace Tests\Feature\Domain\Analytics\Integration;

use App\Domain\Analytics\Contracts\DashboardServiceInterface as AnalyticsDashboardServiceInterface;
use App\Domain\Shared\Money\ValueObjects\Money; // updated namespace
use App\Domain\User\Events\UserDataChanged;
use App\Models\User;
use App\Models\Client;
use App\Models\Invoice;
use App\Domain\User\ValueObjects\UserId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CompleteIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private AnalyticsDashboardServiceInterface $dashboardService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dashboardService = app(AnalyticsDashboardServiceInterface::class);
    }

    #[Test]
    public function full_flow_cache_invalidation_and_aggregation(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $user->id]);

    $initial = $this->dashboardService->getDashboardData(UserId::fromInt($user->id));
        $this->assertSame(0, $initial['statistics']->invoiceCount);
        $this->assertInstanceOf(Money::class, $initial['statistics']->totalAmount);
        $this->assertSame(0.0, $initial['statistics']->totalAmount->toFloat());

        Invoice::factory()->create(['user_id' => $user->id, 'client_id' => $client->id, 'payment_amount' => 25, 'issue_date' => now()->startOfMonth()]);
    $afterFirst = $this->dashboardService->getDashboardData(UserId::fromInt($user->id));
        $this->assertSame(1, $afterFirst['statistics']->invoiceCount);
        $this->assertSame(25.0, $afterFirst['statistics']->totalAmount->toFloat());

        Invoice::withoutEvents(function () use ($user, $client) {
            Invoice::factory()->create(['user_id' => $user->id, 'client_id' => $client->id, 'payment_amount' => 75, 'issue_date' => now()->startOfMonth()->addDay()]);
        });
    $stale = $this->dashboardService->getDashboardData(UserId::fromInt($user->id));
        $this->assertSame(1, $stale['statistics']->invoiceCount);

    app(\Illuminate\Contracts\Events\Dispatcher::class)->dispatch(new UserDataChanged($user->id, 'invoice'));
    $refreshed = $this->dashboardService->getDashboardData(UserId::fromInt($user->id));
        $this->assertSame(2, $refreshed['statistics']->invoiceCount);
        $this->assertSame(100.0, $refreshed['statistics']->totalAmount->toFloat());
    $this->assertGreaterThanOrEqual(1, count($refreshed['monthly_stats']));

        $client2 = Client::withoutEvents(function () use ($user) {
            return Client::factory()->create(['user_id' => $user->id]);
        });
        Invoice::withoutEvents(function () use ($user, $client2) {
            Invoice::factory()->create(['user_id' => $user->id, 'client_id' => $client2->id, 'payment_amount' => 10, 'issue_date' => now()->startOfMonth()->addDays(2)]);
        });
    $staleClients = $this->dashboardService->getDashboardData(UserId::fromInt($user->id));
        $this->assertCount(1, $staleClients['clients']);

    app(\Illuminate\Contracts\Events\Dispatcher::class)->dispatch(new UserDataChanged($user->id, 'client'));
    $refreshedClients = $this->dashboardService->getDashboardData(UserId::fromInt($user->id));
        $this->assertCount(2, $refreshedClients['clients']);
        $this->assertInstanceOf(Money::class, $refreshedClients['statistics']->totalAmount);
    }
}
