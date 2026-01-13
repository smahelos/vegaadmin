<?php

namespace Tests\Unit\Domain\Shared\Money\Services;

use App\Domain\Shared\Cache\Contracts\CacheServiceInterface;
use App\Domain\Shared\Http\Contracts\HttpClientInterface;
use App\Domain\Shared\Log\Contracts\LogInterface;
use App\Domain\Shared\Money\Services\CurrencyService;
use App\Domain\Shared\Money\Contracts\CurrencyServiceInterface;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class CurrencyServiceTest extends TestCase
{
    private CurrencyService $service;
    private HttpClientInterface $mockHttpClient;
    private CacheServiceInterface $mockCacheService;
    private LogInterface $mockLogger;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockHttpClient = Mockery::mock('App\Domain\Shared\Http\Contracts\HttpClientInterface');
        $this->mockCacheService = Mockery::mock('App\Domain\Shared\Cache\Contracts\CacheServiceInterface');
        $this->mockLogger = Mockery::mock('App\Domain\Shared\Log\Contracts\LogInterface');
        
        $this->service = new CurrencyService(
            $this->mockHttpClient,
            $this->mockCacheService,
            $this->mockLogger
        );
    }

    #[Test]
    public function service_can_be_instantiated(): void
    {
        $this->assertInstanceOf(CurrencyService::class, $this->service);
        $this->assertInstanceOf(CurrencyServiceInterface::class, $this->service);
    }

    #[Test]
    public function service_has_api_url_property(): void
    {
        $reflection = new ReflectionClass($this->service);
        $this->assertTrue($reflection->hasProperty('apiUrl'));
        $property = $reflection->getProperty('apiUrl');
        $this->assertEquals('string', (string)$property->getType());
    }

    #[Test]
    public function get_all_currencies_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'getAllCurrencies'));
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('getAllCurrencies');
        $this->assertEquals('array', (string)$method->getReturnType());
        $this->assertCount(0, $method->getParameters());
    }

    #[Test]
    public function get_common_currencies_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'getCommonCurrencies'));
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('getCommonCurrencies');
        $this->assertEquals('array', (string)$method->getReturnType());
    }

    #[Test]
    public function get_fallback_currencies_method_exists(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('getFallbackCurrencies');
        $this->assertTrue($method->isPrivate());
        $this->assertEquals('array', (string)$method->getReturnType());
    }

    #[Test]
    public function service_has_correct_structure(): void
    {
        $reflection = new ReflectionClass($this->service);
        $this->assertEquals('App\\Domain\\Shared\\Money\\Services', $reflection->getNamespaceName());
        $this->assertEquals('CurrencyService', $reflection->getShortName());
    }

    #[Test]
    public function all_public_methods_have_return_types(): void
    {
        $reflection = new ReflectionClass($this->service);
        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() === CurrencyService::class && $method->getName() !== '__construct') {
                $this->assertNotNull($method->getReturnType(), $method->getName().' missing return type');
            }
        }
    }

    #[Test]
    public function public_methods_count(): void
    {
        $reflection = new ReflectionClass($this->service);
        $customPublic = array_filter($reflection->getMethods(\ReflectionMethod::IS_PUBLIC), fn($m) => $m->getDeclaringClass()->getName() === CurrencyService::class && $m->getName() !== '__construct');
        $this->assertCount(2, $customPublic);
    }

    #[Test]
    public function private_methods_count(): void
    {
        $reflection = new ReflectionClass($this->service);
        $customPrivate = array_filter($reflection->getMethods(\ReflectionMethod::IS_PRIVATE), fn($m) => $m->getDeclaringClass()->getName() === CurrencyService::class);
        $this->assertCount(1, $customPrivate);
    }
}
