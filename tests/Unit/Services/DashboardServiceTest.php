<?php

namespace Tests\Unit\Services;

use App\Contracts\DashboardServiceInterface;
use App\Contracts\CacheServiceInterface;
use App\Services\DashboardService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Mockery;

class DashboardServiceTest extends TestCase
{
    private DashboardService $service;
    private $mockCacheService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock CacheServiceInterface
        $this->mockCacheService = Mockery::mock('App\Contracts\CacheServiceInterface');
        
        $this->service = new DashboardService($this->mockCacheService);
    }

    #[Test]
    public function service_can_be_instantiated(): void
    {
        $this->assertInstanceOf(DashboardService::class, $this->service);
    }

    #[Test]
    public function service_implements_dashboard_service_interface(): void
    {
        $this->assertInstanceOf(DashboardServiceInterface::class, $this->service);
    }

    #[Test]
    public function get_user_statistics_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'getUserStatistics'));
    }

    #[Test]
    public function get_monthly_statistics_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'getMonthlyStatistics'));
    }

    #[Test]
    public function get_clients_with_invoice_totals_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'getClientsWithInvoiceTotals'));
    }

    #[Test]
    public function get_dashboard_data_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'getDashboardData'));
    }

    #[Test]
    public function service_has_correct_structure(): void
    {
        $reflection = new \ReflectionClass($this->service);
        
        $this->assertTrue($reflection->hasMethod('getUserStatistics'));
        $this->assertTrue($reflection->hasMethod('getMonthlyStatistics'));
        $this->assertTrue($reflection->hasMethod('getClientsWithInvoiceTotals'));
        $this->assertTrue($reflection->hasMethod('getDashboardData'));
    }

    #[Test]
    public function all_public_methods_have_return_types(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        
        foreach ($methods as $method) {
            if ($method->getDeclaringClass()->getName() === DashboardService::class && 
                $method->getName() !== '__construct') {
                $this->assertNotNull(
                    $method->getReturnType(),
                    "Method {$method->getName()} should have a return type"
                );
            }
        }
    }

    #[Test]
    public function public_methods_count(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $publicMethods = array_filter(
            $reflection->getMethods(\ReflectionMethod::IS_PUBLIC),
            fn($method) => $method->getDeclaringClass()->getName() === DashboardService::class
        );
        
        $this->assertCount(6, $publicMethods, 'DashboardService should have exactly 6 public methods');
    }

    #[Test]
    public function method_parameter_types_are_correct(): void
    {
        $reflection = new \ReflectionClass($this->service);
        
        // Check getUserStatistics method
        $method = $reflection->getMethod('getUserStatistics');
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);
        $this->assertEquals('App\Models\User', $parameters[0]->getType()->getName());
        
        // Check getMonthlyStatistics method
        $method = $reflection->getMethod('getMonthlyStatistics');
        $parameters = $method->getParameters();
        $this->assertCount(2, $parameters);
        $this->assertEquals('App\Models\User', $parameters[0]->getType()->getName());
        $this->assertEquals('int', $parameters[1]->getType()->getName());
        
        // Check getClientsWithInvoiceTotals method
        $method = $reflection->getMethod('getClientsWithInvoiceTotals');
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);
        $this->assertEquals('App\Models\User', $parameters[0]->getType()->getName());
        
        // Check getDashboardData method
        $method = $reflection->getMethod('getDashboardData');
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);
        $this->assertEquals('App\Models\User', $parameters[0]->getType()->getName());
    }
}
