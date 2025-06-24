<?php

namespace Tests\Feature\Services;

use App\Contracts\DashboardServiceInterface;
use App\Models\User;
use App\Models\Client;
use App\Models\Supplier;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\App;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DashboardServiceFeatureTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private DashboardServiceInterface $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = App::make(DashboardServiceInterface::class);
        $this->user = User::factory()->create();
    }

    #[Test]
    public function service_can_be_resolved_from_container(): void
    {
        $service = App::make(DashboardServiceInterface::class);
        $this->assertInstanceOf(DashboardServiceInterface::class, $service);
    }

    #[Test]
    public function get_user_statistics_returns_correct_structure(): void
    {
        // Create test data
        Client::factory()->count(3)->create(['user_id' => $this->user->id]);
        Supplier::factory()->count(2)->create(['user_id' => $this->user->id]);
        Invoice::factory()->count(5)->create(['user_id' => $this->user->id]);

        $statistics = $this->service->getUserStatistics($this->user);

        $this->assertIsArray($statistics);
        $this->assertArrayHasKey('invoice_count', $statistics);
        $this->assertArrayHasKey('client_count', $statistics);
        $this->assertArrayHasKey('suppliers_count', $statistics);
        $this->assertArrayHasKey('total_amount', $statistics);
        
        $this->assertEquals(5, $statistics['invoice_count']);
        $this->assertEquals(3, $statistics['client_count']);
        $this->assertEquals(2, $statistics['suppliers_count']);
        $this->assertIsNumeric($statistics['total_amount']);
    }

    #[Test]
    public function get_user_statistics_returns_zero_for_empty_user(): void
    {
        $statistics = $this->service->getUserStatistics($this->user);

        $this->assertEquals(0, $statistics['invoice_count']);
        $this->assertEquals(0, $statistics['client_count']);
        $this->assertEquals(0, $statistics['suppliers_count']);
        $this->assertEquals(0, $statistics['total_amount']);
    }

    #[Test]
    public function get_monthly_statistics_returns_collection(): void
    {
        // Create client and invoice
        $client = Client::factory()->create(['user_id' => $this->user->id]);
        Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
            'issue_date' => now()->subMonth(),
            'payment_amount' => 1000
        ]);

        $monthlyStats = $this->service->getMonthlyStatistics($this->user);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $monthlyStats);
    }

    #[Test]
    public function get_clients_with_invoice_totals_returns_collection(): void
    {
        // Create clients
        $client1 = Client::factory()->create(['user_id' => $this->user->id]);
        $client2 = Client::factory()->create(['user_id' => $this->user->id]);
        
        // Create invoices
        Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client1->id,
            'payment_amount' => 1000
        ]);
        Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client2->id,
            'payment_amount' => 2000
        ]);

        $clients = $this->service->getClientsWithInvoiceTotals($this->user);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $clients);
        $this->assertCount(2, $clients);
    }

    #[Test]
    public function get_dashboard_data_returns_complete_structure(): void
    {
        // Create test data
        $client = Client::factory()->create(['user_id' => $this->user->id]);
        Supplier::factory()->create(['user_id' => $this->user->id]);
        Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
            'issue_date' => now(),
            'payment_amount' => 1500
        ]);

        $dashboardData = $this->service->getDashboardData($this->user);

        $this->assertIsArray($dashboardData);
        $this->assertArrayHasKey('statistics', $dashboardData);
        $this->assertArrayHasKey('monthly_stats', $dashboardData);
        $this->assertArrayHasKey('clients', $dashboardData);

        // Check statistics structure
        $this->assertArrayHasKey('invoice_count', $dashboardData['statistics']);
        $this->assertArrayHasKey('client_count', $dashboardData['statistics']);
        $this->assertArrayHasKey('suppliers_count', $dashboardData['statistics']);
        $this->assertArrayHasKey('total_amount', $dashboardData['statistics']);

        // Check data types
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $dashboardData['monthly_stats']);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $dashboardData['clients']);
    }

    #[Test]
    public function service_isolates_data_by_user(): void
    {
        // Create another user with data
        $otherUser = User::factory()->create();
        Client::factory()->count(5)->create(['user_id' => $otherUser->id]);
        Invoice::factory()->count(10)->create(['user_id' => $otherUser->id]);

        // Create data for test user
        Client::factory()->count(2)->create(['user_id' => $this->user->id]);
        Invoice::factory()->count(3)->create(['user_id' => $this->user->id]);

        $statistics = $this->service->getUserStatistics($this->user);

        // Should only return data for test user, not other user
        $this->assertEquals(3, $statistics['invoice_count']);
        $this->assertEquals(2, $statistics['client_count']);
    }

    #[Test]
    public function monthly_statistics_respects_time_range(): void
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);
        
        // Create invoice within range
        Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
            'issue_date' => now()->subMonths(2),
            'payment_amount' => 1000
        ]);
        
        // Create invoice outside range
        Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
            'issue_date' => now()->subMonths(8), // Outside 6 month range
            'payment_amount' => 2000
        ]);

        $monthlyStats = $this->service->getMonthlyStatistics($this->user, 6);

        // Should only include invoice within 6 month range
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $monthlyStats);
    }
}
