<?php

namespace Tests\Unit\Traits;

use App\Traits\TaxFormFields;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class TaxFormFieldsTest extends TestCase
{
    #[Test]
    public function trait_exists_and_is_trait(): void
    {
        $reflection = new \ReflectionClass(TaxFormFields::class);
        $this->assertTrue($reflection->isTrait());
        $this->assertFalse($reflection->isInterface());
    }

    #[Test]
    public function trait_has_get_tax_fields_method(): void
    {
        $reflection = new \ReflectionClass(TaxFormFields::class);
        $this->assertTrue($reflection->hasMethod('getTaxFields'));
        
        $method = $reflection->getMethod('getTaxFields');
        $this->assertTrue($method->isProtected());
        
        // Check method return type
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('array', $returnType->getName());
    }

    #[Test]
    public function get_tax_fields_method_has_correct_signature(): void
    {
        $reflection = new \ReflectionClass(TaxFormFields::class);
        $method = $reflection->getMethod('getTaxFields');
        
        // Should have no parameters
        $this->assertCount(0, $method->getParameters());
    }

    #[Test]
    public function trait_has_proper_docblocks(): void
    {
        $reflection = new \ReflectionClass(TaxFormFields::class);
        
        // Check getTaxFields method docblock
        $method = $reflection->getMethod('getTaxFields');
        $docComment = $method->getDocComment();
        $this->assertNotFalse($docComment);
        $this->assertStringContainsString('Get tax form fields definitions', $docComment);
        $this->assertStringContainsString('@return array', $docComment);
    }

    #[Test]
    public function trait_structure_is_correct(): void
    {
        $reflection = new \ReflectionClass(TaxFormFields::class);
        
        // Check namespace
        $this->assertEquals('App\Traits', $reflection->getNamespaceName());
        
        // Check that it's not abstract, final, etc.
        $this->assertFalse($reflection->isAbstract());
        $this->assertFalse($reflection->isFinal());
        $this->assertFalse($reflection->isInstantiable()); // Traits are not instantiable
    }

    #[Test]
    public function trait_has_expected_method_count(): void
    {
        $reflection = new \ReflectionClass(TaxFormFields::class);
        $methods = $reflection->getMethods();
        
        // Should have exactly 1 method
        $this->assertCount(1, $methods);
        
        $methodNames = array_map(fn($method) => $method->getName(), $methods);
        $this->assertContains('getTaxFields', $methodNames);
    }

    #[Test]
    public function method_visibility_is_correct(): void
    {
        $reflection = new \ReflectionClass(TaxFormFields::class);
        $method = $reflection->getMethod('getTaxFields');
        
        $this->assertTrue($method->isProtected());
        $this->assertFalse($method->isPublic());
        $this->assertFalse($method->isPrivate());
        $this->assertFalse($method->isStatic());
    }
}
