<?php

namespace Tests\Unit\Services;

use App\Services\CurrencyService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class CurrencyServiceTest extends TestCase
{
    private CurrencyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CurrencyService();
    }

    #[Test]
    public function service_can_be_instantiated(): void
    {
        $this->assertInstanceOf(CurrencyService::class, $this->service);
    }

    #[Test]
    public function service_has_api_url_property(): void
    {
        $reflection = new ReflectionClass($this->service);
        $this->assertTrue($reflection->hasProperty('apiUrl'));
        
        $property = $reflection->getProperty('apiUrl');
        $this->assertEquals('string', $property->getType()->getName());
    }

    #[Test]
    public function get_all_currencies_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'getAllCurrencies'));
        
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('getAllCurrencies');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('array', $returnType->getName());
        
        // Check that method has no parameters
        $parameters = $method->getParameters();
        $this->assertCount(0, $parameters);
    }

    #[Test]
    public function get_common_currencies_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'getCommonCurrencies'));
        
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('getCommonCurrencies');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('array', $returnType->getName());
        
        // Check that method has no parameters
        $parameters = $method->getParameters();
        $this->assertCount(0, $parameters);
    }

    #[Test]
    public function get_fallback_currencies_method_exists(): void
    {
        $reflection = new ReflectionClass($this->service);
        $this->assertTrue($reflection->hasMethod('getFallbackCurrencies'));
        
        $method = $reflection->getMethod('getFallbackCurrencies');
        $this->assertTrue($method->isPrivate());
        
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('array', $returnType->getName());
    }

    #[Test]
    public function service_has_correct_structure(): void
    {
        $reflection = new ReflectionClass($this->service);
        
        // Check that class is properly structured
        $this->assertEquals('App\Services', $reflection->getNamespaceName());
        $this->assertEquals('CurrencyService', $reflection->getShortName());
        $this->assertFalse($reflection->isAbstract());
        $this->assertFalse($reflection->isInterface());
    }

    #[Test]
    public function all_public_methods_have_return_types(): void
    {
        $reflection = new ReflectionClass($this->service);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        
        foreach ($methods as $method) {
            if ($method->getName() !== '__construct') {
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
        $reflection = new ReflectionClass($this->service);
        $publicMethods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        
        // Filter out inherited methods and constructor
        $customPublicMethods = array_filter($publicMethods, function ($method) {
            return $method->getDeclaringClass()->getName() === CurrencyService::class
                && $method->getName() !== '__construct';
        });
        
        $this->assertCount(2, $customPublicMethods);
    }

    #[Test]
    public function private_methods_count(): void
    {
        $reflection = new ReflectionClass($this->service);
        $privateMethods = $reflection->getMethods(\ReflectionMethod::IS_PRIVATE);
        
        // Filter to only methods declared in this class
        $customPrivateMethods = array_filter($privateMethods, function ($method) {
            return $method->getDeclaringClass()->getName() === CurrencyService::class;
        });
        
        $this->assertCount(1, $customPrivateMethods);
    }
}
