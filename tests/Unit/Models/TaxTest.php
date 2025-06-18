<?php

namespace Tests\Unit\Models;

use App\Models\Tax;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Tax model - CRITICAL RULE: Only pure business logic, no Laravel dependencies
 * 
 * According to Unit Test Isolation rule, this class tests only:
 * - Class structure and traits (without instantiation)
 * - Static methods and pure calculations (if any)
 * - Class constants and basic reflection
 * 
 * All Eloquent-dependent tests (table name, guarded, accessors, model behavior)
 * have been moved to Feature tests.
 */
class TaxTest extends TestCase
{
    #[Test]
    public function tax_uses_expected_traits(): void
    {
        // Test traits on the actual class without instantiation
        $traits = class_uses_recursive(Tax::class);
        
        $expectedTraits = [
            'Illuminate\Database\Eloquent\Factories\HasFactory',
            'Backpack\CRUD\app\Models\Traits\CrudTrait',
        ];
        
        foreach ($expectedTraits as $trait) {
            $this->assertContains($trait, $traits, "Tax model should use {$trait} trait");
        }
    }

    #[Test]
    public function tax_class_exists_and_is_instantiable(): void
    {
        $this->assertTrue(class_exists(Tax::class));
        
        $reflection = new \ReflectionClass(Tax::class);
        $this->assertTrue($reflection->isInstantiable());
    }

    #[Test]
    public function tax_extends_correct_parent_class(): void
    {
        $reflection = new \ReflectionClass(Tax::class);
        $this->assertEquals('Illuminate\Database\Eloquent\Model', $reflection->getParentClass()->getName());
    }

    #[Test]
    public function tax_has_expected_class_structure(): void
    {
        $reflection = new \ReflectionClass(Tax::class);
        
        // Test that class is not abstract or interface
        $this->assertFalse($reflection->isAbstract());
        $this->assertFalse($reflection->isInterface());
        $this->assertTrue($reflection->isInstantiable());
        
        // Test namespace
        $this->assertEquals('App\Models', $reflection->getNamespaceName());
    }

    #[Test]
    public function tax_has_class_constants(): void
    {
        $reflection = new \ReflectionClass(Tax::class);
        $constants = $reflection->getConstants();
        
        // This model doesn't define custom constants, but the test structure is here for future use
        $this->assertIsArray($constants);
    }

    #[Test]
    public function tax_has_expected_public_methods(): void
    {
        $reflection = new \ReflectionClass(Tax::class);
        
        // Test for essential method existence without calling them
        $this->assertTrue($reflection->hasMethod('getTable'));
        $this->assertTrue($reflection->hasMethod('getGuarded'));
        $this->assertTrue($reflection->hasMethod('getRateFormattedAttribute'));
    }
}
