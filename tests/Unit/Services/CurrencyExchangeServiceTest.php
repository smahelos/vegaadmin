<?php

namespace Tests\Unit\Services;

use App\Services\CurrencyExchangeService;
use App\Contracts\CurrencyExchangeServiceInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class CurrencyExchangeServiceTest extends TestCase
{
    private CurrencyExchangeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CurrencyExchangeService();
    }

    #[Test]
    public function service_can_be_instantiated(): void
    {
        $this->assertInstanceOf(CurrencyExchangeService::class, $this->service);
        $this->assertInstanceOf(CurrencyExchangeServiceInterface::class, $this->service);
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
    public function get_exchange_rate_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'getExchangeRate'));
        
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('getExchangeRate');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertTrue($returnType->allowsNull());
        $this->assertEquals('float', $returnType->getName());
        
        // Check parameters
        $parameters = $method->getParameters();
        $this->assertCount(2, $parameters);
        $this->assertEquals('fromCurrency', $parameters[0]->getName());
        $this->assertEquals('toCurrency', $parameters[1]->getName());
        $this->assertEquals('string', $parameters[0]->getType()->getName());
        $this->assertEquals('string', $parameters[1]->getType()->getName());
    }

    #[Test]
    public function get_exchange_rates_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'getExchangeRates'));
        
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('getExchangeRates');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('array', $returnType->getName());
        
        // Check parameters
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);
        $this->assertEquals('baseCurrency', $parameters[0]->getName());
        $this->assertEquals('string', $parameters[0]->getType()->getName());
        $this->assertTrue($parameters[0]->isDefaultValueAvailable());
        $this->assertEquals('USD', $parameters[0]->getDefaultValue());
    }

    #[Test]
    public function convert_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'convert'));
        
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('convert');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertTrue($returnType->allowsNull());
        $this->assertEquals('float', $returnType->getName());
        
        // Check parameters
        $parameters = $method->getParameters();
        $this->assertCount(3, $parameters);
        $this->assertEquals('amount', $parameters[0]->getName());
        $this->assertEquals('fromCurrency', $parameters[1]->getName());
        $this->assertEquals('toCurrency', $parameters[2]->getName());
        $this->assertEquals('float', $parameters[0]->getType()->getName());
        $this->assertEquals('string', $parameters[1]->getType()->getName());
        $this->assertEquals('string', $parameters[2]->getType()->getName());
    }

    #[Test]
    public function get_fallback_rates_method_exists(): void
    {
        $reflection = new ReflectionClass($this->service);
        $this->assertTrue($reflection->hasMethod('getFallbackRates'));
        
        $method = $reflection->getMethod('getFallbackRates');
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
        $this->assertEquals('CurrencyExchangeService', $reflection->getShortName());
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
    public function all_method_parameters_have_types(): void
    {
        $reflection = new ReflectionClass($this->service);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        
        foreach ($methods as $method) {
            if ($method->getName() !== '__construct') {
                foreach ($method->getParameters() as $parameter) {
                    $this->assertNotNull(
                        $parameter->getType(),
                        "Parameter {$parameter->getName()} in method {$method->getName()} should have a type"
                    );
                }
            }
        }
    }
}
