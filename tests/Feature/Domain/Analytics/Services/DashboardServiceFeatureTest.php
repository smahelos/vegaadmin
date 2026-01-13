<?php

namespace Tests\Feature\Domain\Analytics\Services;

use App\Domain\Analytics\Contracts\DashboardServiceInterface as AnalyticsDashboardServiceInterface;
use App\Domain\Analytics\DTO\UserStatisticsDTO;
use App\Domain\Shared\Money\ValueObjects\Money; // updated namespace
use App\Domain\User\ValueObjects\UserId;
use App\Models\User;
use App\Models\Client;
use App\Models\Supplier;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesFrontendTestEnvironment;

class DashboardServiceFeatureTest extends TestCase
{
    use RefreshDatabase, WithFaker, CreatesFrontendTestEnvironment;

    private AnalyticsDashboardServiceInterface $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFrontendTestEnvironment();
        $this->service = app(AnalyticsDashboardServiceInterface::class);
    }

    #[Test]
    public function service_can_be_resolved_from_container(): void
    {
        $service = app(AnalyticsDashboardServiceInterface::class);
        $this->assertInstanceOf(AnalyticsDashboardServiceInterface::class, $service);
    }

    #[Test]
    public function statistics_structure_for_new_user_is_zeroed(): void
    {
        $user = User::factory()->create();
        $stats = $this->service->getUserStatistics(UserId::fromInt($user->id));
        $this->assertSame(0, $stats->invoiceCount);
        $this->assertSame(0, $stats->clientCount);
        $this->assertSame(0, $stats->suppliersCount);
        $this->assertInstanceOf(Money::class, $stats->totalAmount);
        $this->assertSame('0', $stats->totalAmount->getAmount());
        $this->assertSame(0.0, $stats->totalAmount->toFloat());
    }

    #[Test]
    public function statistics_reflect_created_entities(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $user->id]);
        $supplier = Supplier::factory()->create(['user_id' => $user->id]);
        Invoice::factory()->create(['user_id' => $user->id, 'client_id' => $client->id, 'payment_amount' => 123.45]);

        $stats = $this->service->getUserStatistics(UserId::fromInt($user->id));
        $this->assertSame(1, $stats->invoiceCount);
        $this->assertSame(1, $stats->clientCount);
        $this->assertSame(1, $stats->suppliersCount);
        $this->assertInstanceOf(Money::class, $stats->totalAmount);
        $this->assertSame(123.45, $stats->totalAmount->toFloat());
    }

    #[Test]
    public function monthly_statistics_cover_requested_window(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $user->id]);

        // Create invoices across 3 months window (now, -1M, -2M) but request 2 months
        Invoice::factory()->create(['user_id' => $user->id, 'client_id' => $client->id, 'issue_date' => now()->startOfMonth(), 'payment_amount' => 10]);
        Invoice::factory()->create(['user_id' => $user->id, 'client_id' => $client->id, 'issue_date' => now()->subMonth()->startOfMonth(), 'payment_amount' => 20]);
        Invoice::factory()->create(['user_id' => $user->id, 'client_id' => $client->id, 'issue_date' => now()->subMonths(2)->startOfMonth(), 'payment_amount' => 30]);

        $collection = collect($this->service->getMonthlyStatistics(UserId::fromInt($user->id), 2));
        $months = $collection->pluck('month')->all();
        $this->assertContains(now()->format('Y-m'), $months);
        $this->assertContains(now()->subMonth()->format('Y-m'), $months);
        // Ensure each total is Money
        $collection->each(function ($dto) { $this->assertInstanceOf(Money::class, $dto->total); });
    }

    #[Test]
    public function clients_with_totals_returns_expected_shape(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $user->id]);
        Invoice::factory()->create(['user_id' => $user->id, 'client_id' => $client->id, 'payment_amount' => 55]);

        $clients = collect($this->service->getClientsWithInvoiceTotals(UserId::fromInt($user->id)));
        $this->assertCount(1, $clients);
        $first = $clients->first();
        $this->assertSame($client->id, $first->clientId);
        $this->assertInstanceOf(Money::class, $first->totalAmount);
        $this->assertSame(55.0, $first->totalAmount->toFloat());
    }

    #[Test]
    public function dashboard_data_aggregates_all_segments(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $user->id]);
        Invoice::factory()->create(['user_id' => $user->id, 'client_id' => $client->id, 'payment_amount' => 42]);

        $data = $this->service->getDashboardData(UserId::fromInt($user->id));
        $this->assertArrayHasKey('statistics', $data);
        $this->assertArrayHasKey('monthly_stats', $data);
        $this->assertArrayHasKey('clients', $data);
        
        // Domain service returns only raw DTOs, no formatted data
        $this->assertArrayNotHasKey('statistics_formatted', $data);
        $this->assertArrayNotHasKey('monthly_stats_formatted', $data);
        $this->assertArrayNotHasKey('clients_formatted', $data);

        $this->assertSame(1, $data['statistics']->invoiceCount);
        $this->assertInstanceOf(Money::class, $data['statistics']->totalAmount);

        // Test that monthly_stats and clients are arrays (not Collections)
        $this->assertIsArray($data['monthly_stats']);
        $this->assertIsArray($data['clients']);
    }

    #[Test]
    public function statistics_are_isolated_per_user(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $clientA = Client::factory()->create(['user_id' => $userA->id]);
        Invoice::factory()->create(['user_id' => $userA->id, 'client_id' => $clientA->id, 'payment_amount' => 11]);

        $statsA = $this->service->getUserStatistics(UserId::fromInt($userA->id));
        $statsB = $this->service->getUserStatistics(UserId::fromInt($userB->id));
        $this->assertSame(1, $statsA->invoiceCount);
        $this->assertSame(0, $statsB->invoiceCount);
        $this->assertInstanceOf(Money::class, $statsA->totalAmount);
        $this->assertInstanceOf(Money::class, $statsB->totalAmount);
    }
}
