<?php

namespace Tests\Unit\Helpers;

use App\Helpers\DateHelper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DateHelperTest extends TestCase
{
    #[Test]
    public function class_exists_and_is_instantiable(): void
    {
        $this->assertTrue(class_exists(DateHelper::class));
        
        // DateHelper only has static methods, but we can verify the class structure
        $reflection = new \ReflectionClass(DateHelper::class);
        $this->assertFalse($reflection->isAbstract());
        $this->assertFalse($reflection->isInterface());
        $this->assertFalse($reflection->isTrait());
    }

    #[Test]
    public function format_method_exists_and_is_static(): void
    {
        $this->assertTrue(method_exists(DateHelper::class, 'format'));
        
        $reflection = new \ReflectionClass(DateHelper::class);
        $method = $reflection->getMethod('format');
        
        $this->assertTrue($method->isStatic());
        $this->assertTrue($method->isPublic());
    }

    #[Test]
    public function format_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass(DateHelper::class);
        $method = $reflection->getMethod('format');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('string', $returnType->getName());
    }

    #[Test]
    public function format_method_has_no_parameters(): void
    {
        $reflection = new \ReflectionClass(DateHelper::class);
        $method = $reflection->getMethod('format');
        
        $this->assertCount(0, $method->getParameters());
    }

    #[Test]
    public function class_has_expected_namespace(): void
    {
        $reflection = new \ReflectionClass(DateHelper::class);
        
        $this->assertEquals('App\Helpers', $reflection->getNamespaceName());
    }

    #[Test]
    public function class_has_correct_method_count(): void
    {
        $reflection = new \ReflectionClass(DateHelper::class);
        $publicMethods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        
        // Should have only the format method
        $this->assertCount(1, $publicMethods);
        $this->assertEquals('format', $publicMethods[0]->getName());
    }

    #[Test]
    public function class_structure_is_correct(): void
    {
        $reflection = new \ReflectionClass(DateHelper::class);
        
        // Should not extend any class
        $this->assertFalse($reflection->getParentClass());
        
        // Should not implement any interfaces
        $this->assertEmpty($reflection->getInterfaceNames());
        
        // Should not use any traits
        $this->assertEmpty($reflection->getTraitNames());
    }

    #[Test]
    public function format_method_has_proper_docblock(): void
    {
        $reflection = new \ReflectionClass(DateHelper::class);
        $method = $reflection->getMethod('format');
        $docComment = $method->getDocComment();
        
        $this->assertNotFalse($docComment);
        $this->assertStringContainsString('Return date format based on current language', $docComment);
        $this->assertStringContainsString('@return string', $docComment);
    }
}
