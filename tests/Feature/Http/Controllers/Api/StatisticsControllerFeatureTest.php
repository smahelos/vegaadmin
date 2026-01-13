<?php

namespace Tests\Feature\Http\Controllers\Api;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Application\Analytics\Contracts\ApiStatisticsApplicationServiceInterface;

class StatisticsControllerFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Bind fake service to avoid MySQL-specific SQL (STR_TO_DATE) on SQLite
        $this->app->bind(ApiStatisticsApplicationServiceInterface::class, fn() => new class implements ApiStatisticsApplicationServiceInterface {
            public function monthlyRevenue($request): array { return [ ['month' => '2025-01', 'total' => 1000, 'paid' => 900] ]; }
            public function clientRevenue($request): array { return auth()->check() ? [ ['client_id' => 1, 'client_name' => 'Acme', 'total' => 123.45] ] : []; }
            public function invoiceStatus($request): array { return [ ['status' => 'paid', 'count' => 5] ]; }
            public function paymentMethods($request): array { return [ ['method' => 'card', 'method_name' => 'Card', 'total' => 200, 'count' => 3] ]; }
            public function revenueExpenses($request): array { return [ ['month' => '2025-01', 'amount' => 1000, 'type' => 'revenue'], ['month' => '2025-01', 'amount' => 300, 'type' => 'expense'] ]; }
        });
    }

    private function makeUser(): User
    {
        return User::factory()->create(['password' => Hash::make('secret123')]);
    }

    /** @test */
    public function monthly_revenue_returns_data(): void
    {
        $resp = $this->getJson(route('api.statistics.monthly-revenue'));
        $resp->assertOk()->assertJsonStructure(['data']);
        $this->assertEquals('2025-01', $resp->json('data.0.month'));
    }

    /** @test */
    public function client_revenue_returns_empty_when_not_authenticated(): void
    {
        $resp = $this->getJson(route('api.statistics.client-revenue'));
        $resp->assertOk();
        $this->assertSame([], $resp->json());
    }

    /** @test */
    public function client_revenue_returns_data_when_authenticated(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user, 'web');
        $resp = $this->getJson(route('api.statistics.client-revenue'));
        $resp->assertOk();
        $this->assertNotEmpty($resp->json());
        $this->assertEquals('Acme', $resp->json('0.client_name'));
    }

    /** @test */
    public function invoice_status_returns_distribution(): void
    {
        $resp = $this->getJson(route('api.statistics.invoice-status'));
        $resp->assertOk();
        $this->assertEquals('paid', $resp->json('data.0.status'));
    }

    /** @test */
    public function payment_methods_returns_usage(): void
    {
        $resp = $this->getJson(route('api.statistics.payment-methods'));
        $resp->assertOk();
        $this->assertEquals('card', $resp->json('data.0.method'));
    }

    /** @test */
    public function revenue_expenses_returns_timeline(): void
    {
        $resp = $this->getJson(route('api.statistics.revenue-expenses'));
        $resp->assertOk();
        $this->assertCount(2, $resp->json('data'));
    }
}
