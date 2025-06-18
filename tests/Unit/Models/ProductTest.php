<?php

namespace Tests\Unit\Models;

use App\Models\Product;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Product model - CRITICAL RULE: Only pure business logic, no Laravel dependencies
 * 
 * According to Unit Test Isolation rule, this class tests only:
 * - Class structure and traits (without instantiation)
 * - Static methods and pure calculations (if any)
 * - Class constants and basic reflection
 * 
 * All Eloquent-dependent tests (fillable, casts, table name, relationships, sluggable)
 * have been moved to Feature tests.
 */
class ProductTest extends TestCase
{
    #[Test]
    public function product_uses_expected_traits(): void
    {
        // Test traits on the actual class without instantiation
        $traits = class_uses_recursive(Product::class);
        
        $expectedTraits = [
            'Illuminate\Database\Eloquent\Factories\HasFactory',
            'Cviebrock\EloquentSluggable\Sluggable',
            'Backpack\CRUD\app\Models\Traits\CrudTrait',
            'App\Traits\HasFileUploads',
        ];
        
        foreach ($expectedTraits as $trait) {
            $this->assertContains($trait, $traits, "Product model should use {$trait} trait");
        }
    }

    #[Test]
    public function product_class_exists_and_is_instantiable(): void
    {
        $this->assertTrue(class_exists(Product::class));
        
        $reflection = new \ReflectionClass(Product::class);
        $this->assertTrue($reflection->isInstantiable());
    }

    #[Test]
    public function product_extends_correct_parent_class(): void
    {
        $reflection = new \ReflectionClass(Product::class);
        $this->assertEquals('Illuminate\Database\Eloquent\Model', $reflection->getParentClass()->getName());
    }

    #[Test]
    public function product_has_expected_class_structure(): void
    {
        $reflection = new \ReflectionClass(Product::class);
        
        // Test that class is not abstract or interface
        $this->assertFalse($reflection->isAbstract());
        $this->assertFalse($reflection->isInterface());
        $this->assertTrue($reflection->isInstantiable());
        
        // Test namespace
        $this->assertEquals('App\Models', $reflection->getNamespaceName());
    }

    #[Test]
    public function product_has_class_constants(): void
    {
        $reflection = new \ReflectionClass(Product::class);
        $constants = $reflection->getConstants();
        
        // This model doesn't define custom constants, but the test structure is here for future use
        $this->assertIsArray($constants);
    }

    #[Test]
    public function product_has_expected_public_methods(): void
    {
        $reflection = new \ReflectionClass(Product::class);
        
        // Test for essential method existence without calling them
        $this->assertTrue($reflection->hasMethod('getFillable'));
        $this->assertTrue($reflection->hasMethod('getCasts'));
        $this->assertTrue($reflection->hasMethod('getTable'));
        
        // Test for sluggable method
        $this->assertTrue($reflection->hasMethod('sluggable'));
        
        // Test for relationship methods
        $this->assertTrue($reflection->hasMethod('invoices'));
        $this->assertTrue($reflection->hasMethod('user'));
        $this->assertTrue($reflection->hasMethod('supplier'));
        $this->assertTrue($reflection->hasMethod('tax'));
        $this->assertTrue($reflection->hasMethod('category'));
        
        // Test for accessor/mutator methods
        $this->assertTrue($reflection->hasMethod('getInvoiceCountAttribute'));
        $this->assertTrue($reflection->hasMethod('setImageAttribute'));
        $this->assertTrue($reflection->hasMethod('getFileUrl'));
        $this->assertTrue($reflection->hasMethod('getImageThumbUrl'));
    }

    #[Test]
    public function sluggable_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass(Product::class);
        $method = $reflection->getMethod('sluggable');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('array', $returnType->getName());
        $this->assertTrue($method->isPublic());
        $this->assertFalse($method->isStatic());
    }

    #[Test]
    public function accessor_and_mutator_methods_exist(): void
    {
        $reflection = new \ReflectionClass(Product::class);
        
        // Test accessor methods
        $invoiceCountMethod = $reflection->getMethod('getInvoiceCountAttribute');
        $this->assertTrue($invoiceCountMethod->isPublic());
        $this->assertFalse($invoiceCountMethod->isStatic());
        
        // Test mutator methods
        $imageMethod = $reflection->getMethod('setImageAttribute');
        $this->assertTrue($imageMethod->isPublic());
        $this->assertFalse($imageMethod->isStatic());
        $this->assertEquals(1, $imageMethod->getNumberOfParameters());
        
        // Test file upload methods
        $fileUrlMethod = $reflection->getMethod('getFileUrl');
        $this->assertTrue($fileUrlMethod->isPublic());
        $this->assertFalse($fileUrlMethod->isStatic());
        
        $thumbUrlMethod = $reflection->getMethod('getImageThumbUrl');
        $this->assertTrue($thumbUrlMethod->isPublic());
        $this->assertFalse($thumbUrlMethod->isStatic());
    }

    #[Test]
    public function relationship_methods_exist_and_are_public(): void
    {
        $reflection = new \ReflectionClass(Product::class);
        
        $relationshipMethods = ['invoices', 'user', 'supplier', 'tax', 'category'];
        
        foreach ($relationshipMethods as $methodName) {
            $method = $reflection->getMethod($methodName);
            $this->assertTrue($method->isPublic(), "{$methodName} method should be public");
            $this->assertFalse($method->isStatic(), "{$methodName} method should not be static");
        }
    }
}
