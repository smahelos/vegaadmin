<?php

namespace Tests\Unit\Traits;

use App\Traits\SupplierFormFields;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SupplierFormFieldsTest extends TestCase
{
    #[Test]
    public function trait_exists_and_is_trait(): void
    {
        $reflection = new \ReflectionClass(SupplierFormFields::class);
        $this->assertTrue($reflection->isTrait());
        $this->assertFalse($reflection->isInterface());
    }

    #[Test]
    public function trait_has_get_supplier_fields_method(): void
    {
        $reflection = new \ReflectionClass(SupplierFormFields::class);
        $this->assertTrue($reflection->hasMethod('getSupplierFields'));
        
        $method = $reflection->getMethod('getSupplierFields');
        $this->assertTrue($method->isProtected());
        
        // Check method return type
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('array', $returnType->getName());
    }

    #[Test]
    public function get_supplier_fields_method_has_correct_signature(): void
    {
        $reflection = new \ReflectionClass(SupplierFormFields::class);
        $method = $reflection->getMethod('getSupplierFields');
        
        // Should have no parameters
        $this->assertCount(0, $method->getParameters());
    }

    #[Test]
    public function trait_has_proper_docblocks(): void
    {
        $reflection = new \ReflectionClass(SupplierFormFields::class);
        
        // Check getSupplierFields method docblock
        $method = $reflection->getMethod('getSupplierFields');
        $docComment = $method->getDocComment();
        $this->assertNotFalse($docComment);
        $this->assertStringContainsString('Get supplier form fields definitions', $docComment);
        $this->assertStringContainsString('@return array', $docComment);
    }

    #[Test]
    public function trait_structure_is_correct(): void
    {
        $reflection = new \ReflectionClass(SupplierFormFields::class);
        
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
        $reflection = new \ReflectionClass(SupplierFormFields::class);
        $methods = $reflection->getMethods();
        
        // Should have exactly 1 method
        $this->assertCount(1, $methods);
        
        $methodNames = array_map(fn($method) => $method->getName(), $methods);
        $this->assertContains('getSupplierFields', $methodNames);
    }

    #[Test]
    public function method_visibility_is_correct(): void
    {
        $reflection = new \ReflectionClass(SupplierFormFields::class);
        $method = $reflection->getMethod('getSupplierFields');
        
        $this->assertTrue($method->isProtected());
        $this->assertFalse($method->isPublic());
        $this->assertFalse($method->isPrivate());
        $this->assertFalse($method->isStatic());
    }
}
