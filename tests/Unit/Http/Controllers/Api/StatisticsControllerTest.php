<?php

namespace Tests\Unit\Http\Controllers\Api;

use App\Http\Controllers\Api\StatisticsController;
use App\Application\Analytics\Contracts\ApiStatisticsApplicationServiceInterface;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StatisticsControllerTest extends TestCase
{
    private StatisticsController $controller;
    private FakeStatsApp $stats;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stats = new FakeStatsApp();
        $this->controller = new StatisticsController($this->stats);
    }

    #[Test]
    public function controller_instantiates(): void
    {
        $this->assertInstanceOf(StatisticsController::class, $this->controller);
    }

    #[Test]
    public function monthly_revenue_returns_json(): void
    {
        $r = $this->controller->monthlyRevenue(new Request());
        $this->assertEquals(200, $r->status());
        $this->assertEquals('2025-01', $r->getData(true)['data'][0]['month']);
    }

    #[Test]
    public function client_revenue_returns_json(): void
    {
        $r = $this->controller->clientRevenue(new Request());
        $this->assertEquals(200, $r->status());
        $this->assertEquals('Acme', $r->getData(true)[0]['client_name']);
    }

    #[Test]
    public function invoice_status_returns_json(): void
    {
        $r = $this->controller->invoiceStatus(new Request());
        $this->assertEquals(200, $r->status());
        $this->assertEquals('paid', $r->getData(true)['data'][0]['status']);
    }

    #[Test]
    public function payment_methods_returns_json(): void
    {
        $r = $this->controller->paymentMethods(new Request());
        $this->assertEquals(200, $r->status());
        $this->assertEquals('card', $r->getData(true)['data'][0]['method']);
    }

    #[Test]
    public function revenue_expenses_returns_json(): void
    {
        $r = $this->controller->revenueExpenses(new Request());
        $this->assertEquals(200, $r->status());
        $this->assertCount(2, $r->getData(true)['data']);
    }
}

class FakeStatsApp implements ApiStatisticsApplicationServiceInterface
{
    public function monthlyRevenue($request): array { return [['month'=>'2025-01','total'=>100,'paid'=>90]]; }
    public function clientRevenue($request): array { return [['client_id'=>1,'client_name'=>'Acme','total'=>123.45]]; }
    public function invoiceStatus($request): array { return [['status'=>'paid','count'=>5]]; }
    public function paymentMethods($request): array { return [['method'=>'card','method_name'=>'Card','total'=>50,'count'=>2]]; }
    public function revenueExpenses($request): array { return [['month'=>'2025-01','amount'=>100,'type'=>'revenue'],['month'=>'2025-01','amount'=>30,'type'=>'expense']]; }
}
