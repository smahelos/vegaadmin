<?php

namespace Tests\Unit\Services;

use App\Services\LocaleService;
use App\Contracts\LocaleServiceInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class LocaleServiceTest extends TestCase
{
    private LocaleService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LocaleService();
    }

    #[Test]
    public function service_can_be_instantiated(): void
    {
        $this->assertInstanceOf(LocaleService::class, $this->service);
        $this->assertInstanceOf(LocaleServiceInterface::class, $this->service);
    }

    #[Test]
    public function get_available_locales_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'getAvailableLocales'));
        
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('getAvailableLocales');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('array', $returnType->getName());
        
        // Check that method has no parameters
        $parameters = $method->getParameters();
        $this->assertCount(0, $parameters);
    }

    #[Test]
    public function determine_locale_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'determineLocale'));
        
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('determineLocale');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('string', $returnType->getName());
        
        // Check parameters
        $parameters = $method->getParameters();
        $this->assertCount(2, $parameters);
        $this->assertEquals('requestLocale', $parameters[0]->getName());
        $this->assertEquals('dataLocale', $parameters[1]->getName());
        
        $this->assertTrue($parameters[0]->getType()->allowsNull());
        $this->assertEquals('string', $parameters[0]->getType()->getName());
        $this->assertTrue($parameters[1]->getType()->allowsNull());
        $this->assertEquals('string', $parameters[1]->getType()->getName());
        
        // Check default values
        $this->assertTrue($parameters[0]->isDefaultValueAvailable());
        $this->assertNull($parameters[0]->getDefaultValue());
        $this->assertTrue($parameters[1]->isDefaultValueAvailable());
        $this->assertNull($parameters[1]->getDefaultValue());
    }

    #[Test]
    public function set_locale_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'setLocale'));
        
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('setLocale');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('void', $returnType->getName());
        
        // Check parameters
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);
        $this->assertEquals('locale', $parameters[0]->getName());
        $this->assertEquals('string', $parameters[0]->getType()->getName());
    }

    #[Test]
    public function service_has_correct_structure(): void
    {
        $reflection = new ReflectionClass($this->service);
        
        // Check that class is properly structured
        $this->assertEquals('App\Services', $reflection->getNamespaceName());
        $this->assertEquals('LocaleService', $reflection->getShortName());
        $this->assertFalse($reflection->isAbstract());
        $this->assertFalse($reflection->isInterface());
    }

    #[Test]
    public function all_public_methods_have_return_types(): void
    {
        $reflection = new ReflectionClass($this->service);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        
        foreach ($methods as $method) {
            if ($method->getName() !== '__construct' && 
                $method->getDeclaringClass()->getName() === LocaleService::class) {
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
            return $method->getDeclaringClass()->getName() === LocaleService::class
                && $method->getName() !== '__construct';
        });
        
        $this->assertCount(3, $customPublicMethods);
    }

    #[Test]
    public function method_parameter_types_are_correct(): void
    {
        $reflection = new ReflectionClass($this->service);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        
        foreach ($methods as $method) {
            if ($method->getName() !== '__construct' && 
                $method->getDeclaringClass()->getName() === LocaleService::class) {
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
