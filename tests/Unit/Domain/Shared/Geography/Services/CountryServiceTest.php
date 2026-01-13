<?php

namespace Tests\Unit\Domain\Shared\Geography\Services;

use App\Domain\Shared\Cache\Contracts\CacheServiceInterface;
use App\Domain\Shared\Geography\Services\CountryService;
use App\Domain\Shared\Geography\Contracts\CountryServiceInterface;
use App\Domain\Shared\Http\Contracts\HttpClientInterface;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class CountryServiceTest extends TestCase
{
    private CountryService $service;
    private HttpClientInterface $mockHttpClient;
    private CacheServiceInterface $mockCacheService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockHttpClient = Mockery::mock('App\Domain\Shared\Http\Contracts\HttpClientInterface');
        $this->mockCacheService = Mockery::mock('App\Domain\Shared\Cache\Contracts\CacheServiceInterface');
        
        $this->service = new CountryService(
            $this->mockHttpClient,
            $this->mockCacheService
        );
    }

    #[Test]
    public function service_can_be_instantiated(): void
    {
        $this->assertInstanceOf(CountryService::class, $this->service);
        $this->assertInstanceOf(CountryServiceInterface::class, $this->service);
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
    public function get_all_countries_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'getAllCountries'));
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('getAllCountries');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('array', (string)$returnType);
        $this->assertCount(0, $method->getParameters());
    }

    #[Test]
    public function get_countries_for_select_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'getCountriesForSelect'));
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('getCountriesForSelect');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('array', (string)$returnType);
    }

    #[Test]
    public function get_simple_countries_for_select_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'getSimpleCountriesForSelect'));
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('getSimpleCountriesForSelect');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('array', (string)$returnType);
    }

    #[Test]
    public function get_country_codes_for_select_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'getCountryCodesForSelect'));
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('getCountryCodesForSelect');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('array', (string)$returnType);
    }

    #[Test]
    public function get_country_by_code_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'getCountryByCode'));
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('getCountryByCode');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertTrue($returnType->allowsNull());
        $this->assertEquals('?array', (string)$returnType);
    }

    #[Test]
    public function get_fallback_country_codes_method_exists(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('getFallbackCountryCodes');
        $this->assertTrue($method->isPrivate());
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('array', (string)$returnType);
    }

    #[Test]
    public function get_fallback_country_codes_formatted_method_exists(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('getFallbackCountryCodesFormatted');
        $this->assertTrue($method->isPrivate());
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('array', (string)$returnType);
    }

    #[Test]
    public function service_has_correct_structure(): void
    {
        $reflection = new ReflectionClass($this->service);
        $this->assertEquals('App\\Domain\\Shared\\Geography\\Services', $reflection->getNamespaceName());
        $this->assertEquals('CountryService', $reflection->getShortName());
        $this->assertFalse($reflection->isAbstract());
        $this->assertFalse($reflection->isInterface());
    }
}
