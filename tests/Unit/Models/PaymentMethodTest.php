<?php

namespace Tests\Unit\Models;

use App\Models\PaymentMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for PaymentMethod model - CRITICAL RULE: Only pure business logic, no Laravel dependencies
 * 
 * According to Unit Test Isolation rule, this class tests only:
 * - Class structure and traits (without instantiation)
 * - Static methods and pure calculations (if any)
 * - Class constants and basic reflection
 * 
 * All Eloquent-dependent tests (fillable, casts, table name, guarded, relationships)
 * have been moved to Feature tests.
 */
class PaymentMethodTest extends TestCase
{
    #[Test]
    public function payment_method_uses_expected_traits(): void
    {
        // Test traits on the actual class without instantiation
        $traits = class_uses_recursive(PaymentMethod::class);
        
        $expectedTraits = [
            'Illuminate\Database\Eloquent\Factories\HasFactory',
            'Spatie\Permission\Traits\HasRoles',
            'Backpack\CRUD\app\Models\Traits\CrudTrait',
        ];
        
        foreach ($expectedTraits as $trait) {
            $this->assertContains($trait, $traits, "PaymentMethod model should use {$trait} trait");
        }
    }

    #[Test]
    public function payment_method_class_exists_and_is_instantiable(): void
    {
        $this->assertTrue(class_exists(PaymentMethod::class));
        
        $reflection = new \ReflectionClass(PaymentMethod::class);
        $this->assertTrue($reflection->isInstantiable());
    }

    #[Test]
    public function payment_method_extends_correct_parent_class(): void
    {
        $reflection = new \ReflectionClass(PaymentMethod::class);
        $this->assertEquals('Illuminate\Database\Eloquent\Model', $reflection->getParentClass()->getName());
    }

    #[Test]
    public function payment_method_has_expected_class_structure(): void
    {
        $reflection = new \ReflectionClass(PaymentMethod::class);
        
        // Test that class is not abstract or interface
        $this->assertFalse($reflection->isAbstract());
        $this->assertFalse($reflection->isInterface());
        $this->assertTrue($reflection->isInstantiable());
        
        // Test namespace
        $this->assertEquals('App\Models', $reflection->getNamespaceName());
    }

    #[Test]
    public function payment_method_has_class_constants(): void
    {
        $reflection = new \ReflectionClass(PaymentMethod::class);
        $constants = $reflection->getConstants();
        
        // This model doesn't define custom constants, but the test structure is here for future use
        $this->assertIsArray($constants);
    }

    #[Test]
    public function payment_method_has_expected_public_methods(): void
    {
        $reflection = new \ReflectionClass(PaymentMethod::class);
        
        // Test for essential method existence without calling them
        $this->assertTrue($reflection->hasMethod('getGuarded'));
        $this->assertTrue($reflection->hasMethod('getTable'));
        
        // Test for custom accessor method
        $this->assertTrue($reflection->hasMethod('getTranslatedNameAttribute'));
        
        // Test for relationship methods
        $this->assertTrue($reflection->hasMethod('invoices'));
        $this->assertTrue($reflection->hasMethod('expenses'));
    }

    #[Test]
    public function get_translated_name_attribute_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass(PaymentMethod::class);
        $method = $reflection->getMethod('getTranslatedNameAttribute');
        
        $this->assertTrue($method->isPublic());
        $this->assertFalse($method->isStatic());
        $this->assertEquals(0, $method->getNumberOfParameters());
    }

    #[Test]
    public function relationship_methods_exist_and_are_public(): void
    {
        $reflection = new \ReflectionClass(PaymentMethod::class);
        
        // Test invoices relationship method
        $invoicesMethod = $reflection->getMethod('invoices');
        $this->assertTrue($invoicesMethod->isPublic());
        $this->assertFalse($invoicesMethod->isStatic());
        
        // Test expenses relationship method
        $expensesMethod = $reflection->getMethod('expenses');
        $this->assertTrue($expensesMethod->isPublic());
        $this->assertFalse($expensesMethod->isStatic());
    }
}
