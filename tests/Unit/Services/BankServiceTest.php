<?php

namespace Tests\Unit\Services;

use App\Services\BankService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class BankServiceTest extends TestCase
{
    #[Test]
    public function service_can_be_instantiated(): void
    {
        $service = new BankService();
        $this->assertInstanceOf(BankService::class, $service);
    }

    #[Test]
    public function service_has_get_banks_for_dropdown_method(): void
    {
        $reflection = new \ReflectionClass(BankService::class);
        $this->assertTrue($reflection->hasMethod('getBanksForDropdown'));
        
        $method = $reflection->getMethod('getBanksForDropdown');
        $this->assertTrue($method->isPublic());
        
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('array', $returnType->getName());
    }

    #[Test]
    public function get_banks_for_dropdown_method_has_correct_parameters(): void
    {
        $reflection = new \ReflectionClass(BankService::class);
        $method = $reflection->getMethod('getBanksForDropdown');
        
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);
        
        $param = $parameters[0];
        $this->assertEquals('country', $param->getName());
        $this->assertTrue($param->hasType());
        $this->assertEquals('string', $param->getType()->getName());
        $this->assertTrue($param->isDefaultValueAvailable());
        $this->assertEquals('CZ', $param->getDefaultValue());
    }

    #[Test]
    public function service_has_get_banks_for_js_method(): void
    {
        $reflection = new \ReflectionClass(BankService::class);
        $this->assertTrue($reflection->hasMethod('getBanksForJs'));
        
        $method = $reflection->getMethod('getBanksForJs');
        $this->assertTrue($method->isPublic());
        
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('array', $returnType->getName());
    }

    #[Test]
    public function get_banks_for_js_method_has_correct_parameters(): void
    {
        $reflection = new \ReflectionClass(BankService::class);
        $method = $reflection->getMethod('getBanksForJs');
        
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);
        
        $param = $parameters[0];
        $this->assertEquals('country', $param->getName());
        $this->assertTrue($param->hasType());
        $this->assertEquals('string', $param->getType()->getName());
        $this->assertTrue($param->isDefaultValueAvailable());
        $this->assertEquals('CZ', $param->getDefaultValue());
    }

    #[Test]
    public function class_structure_is_correct(): void
    {
        $reflection = new \ReflectionClass(BankService::class);
        
        $this->assertTrue($reflection->isInstantiable());
        $this->assertFalse($reflection->isAbstract());
        $this->assertFalse($reflection->isInterface());
        $this->assertFalse($reflection->isTrait());
    }
}
