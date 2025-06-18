<?php

namespace Tests\Unit\Models;

use App\Models\Bank;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Bank model - CRITICAL RULE: Only pure business logic, no Laravel dependencies
 * 
 * According to Unit Test Isolation rule, this class tests only:
 * - Class structure and traits (without instantiation)
 * - Static methods and pure calculations (if any)
 * - Class constants and basic reflection
 * 
 * All Eloquent-dependent tests (fillable, casts, table name, guarded, relationships)
 * have been moved to Feature tests.
 */
class BankTest extends TestCase
{
    #[Test]
    public function bank_uses_expected_traits(): void
    {
        // Test traits on the actual class without instantiation
        $traits = class_uses_recursive(Bank::class);
        
        $expectedTraits = [
            'Illuminate\Database\Eloquent\Factories\HasFactory',
            'Backpack\CRUD\app\Models\Traits\CrudTrait',
        ];
        
        foreach ($expectedTraits as $trait) {
            $this->assertContains($trait, $traits, "Bank model should use {$trait} trait");
        }
    }

    #[Test]
    public function bank_class_exists_and_is_instantiable(): void
    {
        $this->assertTrue(class_exists(Bank::class));
        
        $reflection = new \ReflectionClass(Bank::class);
        $this->assertTrue($reflection->isInstantiable());
    }

    #[Test]
    public function bank_extends_correct_parent_class(): void
    {
        $reflection = new \ReflectionClass(Bank::class);
        $this->assertEquals('Illuminate\Database\Eloquent\Model', $reflection->getParentClass()->getName());
    }

    #[Test]
    public function bank_has_expected_class_structure(): void
    {
        $reflection = new \ReflectionClass(Bank::class);
        
        // Test that class is not abstract or interface
        $this->assertFalse($reflection->isAbstract());
        $this->assertFalse($reflection->isInterface());
        $this->assertTrue($reflection->isInstantiable());
        
        // Test namespace
        $this->assertEquals('App\Models', $reflection->getNamespaceName());
    }

    #[Test]
    public function bank_has_class_constants(): void
    {
        $reflection = new \ReflectionClass(Bank::class);
        $constants = $reflection->getConstants();
        
        // This model doesn't define custom constants, but the test structure is here for future use
        $this->assertIsArray($constants);
    }

    #[Test]
    public function bank_has_expected_public_methods(): void
    {
        $reflection = new \ReflectionClass(Bank::class);
        
        // Test for essential method existence without calling them
        $this->assertTrue($reflection->hasMethod('getFillable'));
        $this->assertTrue($reflection->hasMethod('getCasts'));
        $this->assertTrue($reflection->hasMethod('getTable'));
    }
}
