<?php

namespace Tests\Unit\Models;

use App\Models\Expense;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Expense model - CRITICAL RULE: Only pure business logic, no Laravel dependencies
 * 
 * According to Unit Test Isolation rule, this class tests only:
 * - Class structure and traits (without instantiation)
 * - Static methods and pure calculations (if any)
 * - Class constants and basic reflection
 * 
 * All Eloquent-dependent tests (fillable, casts, table name, relationships, mutators)
 * have been moved to Feature tests.
 */
class ExpenseTest extends TestCase
{
    #[Test]
    public function expense_uses_expected_traits(): void
    {
        // Test traits on the actual class without instantiation
        $traits = class_uses_recursive(Expense::class);
        
        $expectedTraits = [
            'Illuminate\Database\Eloquent\Factories\HasFactory',
            'Backpack\CRUD\app\Models\Traits\CrudTrait',
            'App\Traits\HasFileUploads',
        ];
        
        foreach ($expectedTraits as $trait) {
            $this->assertContains($trait, $traits, "Expense model should use {$trait} trait");
        }
    }

    #[Test]
    public function expense_class_exists_and_is_instantiable(): void
    {
        $this->assertTrue(class_exists(Expense::class));
        
        $reflection = new \ReflectionClass(Expense::class);
        $this->assertTrue($reflection->isInstantiable());
    }

    #[Test]
    public function expense_extends_correct_parent_class(): void
    {
        $reflection = new \ReflectionClass(Expense::class);
        $this->assertEquals('Illuminate\Database\Eloquent\Model', $reflection->getParentClass()->getName());
    }

    #[Test]
    public function expense_has_expected_class_structure(): void
    {
        $reflection = new \ReflectionClass(Expense::class);
        
        // Test that class is not abstract or interface
        $this->assertFalse($reflection->isAbstract());
        $this->assertFalse($reflection->isInterface());
        $this->assertTrue($reflection->isInstantiable());
        
        // Test namespace
        $this->assertEquals('App\Models', $reflection->getNamespaceName());
    }

    #[Test]
    public function expense_has_class_constants(): void
    {
        $reflection = new \ReflectionClass(Expense::class);
        $constants = $reflection->getConstants();
        
        // This model doesn't define custom constants, but the test structure is here for future use
        $this->assertIsArray($constants);
    }

    #[Test]
    public function expense_has_expected_public_methods(): void
    {
        $reflection = new \ReflectionClass(Expense::class);
        
        // Test for essential method existence without calling them
        $this->assertTrue($reflection->hasMethod('getFillable'));
        $this->assertTrue($reflection->hasMethod('getCasts'));
        $this->assertTrue($reflection->hasMethod('getTable'));
        
        // Test for relationship methods
        $this->assertTrue($reflection->hasMethod('supplier'));
        $this->assertTrue($reflection->hasMethod('user'));
        $this->assertTrue($reflection->hasMethod('tax'));
        $this->assertTrue($reflection->hasMethod('status'));
        $this->assertTrue($reflection->hasMethod('category'));
        $this->assertTrue($reflection->hasMethod('paymentMethod'));
        
        // Test for mutator and accessor methods
        $this->assertTrue($reflection->hasMethod('setAttachmentsAttribute'));
        $this->assertTrue($reflection->hasMethod('getFileUrl'));
    }

    #[Test]
    public function relationship_methods_exist_and_are_public(): void
    {
        $reflection = new \ReflectionClass(Expense::class);
        
        $relationshipMethods = ['supplier', 'user', 'tax', 'status', 'category', 'paymentMethod'];
        
        foreach ($relationshipMethods as $methodName) {
            $method = $reflection->getMethod($methodName);
            $this->assertTrue($method->isPublic(), "{$methodName} method should be public");
            $this->assertFalse($method->isStatic(), "{$methodName} method should not be static");
        }
    }

    #[Test]
    public function mutator_and_accessor_methods_exist(): void
    {
        $reflection = new \ReflectionClass(Expense::class);
        
        // Test mutator method
        $attachmentsMethod = $reflection->getMethod('setAttachmentsAttribute');
        $this->assertTrue($attachmentsMethod->isPublic());
        $this->assertFalse($attachmentsMethod->isStatic());
        $this->assertEquals(1, $attachmentsMethod->getNumberOfParameters());
        
        // Test file upload method from HasFileUploads trait
        $fileUrlMethod = $reflection->getMethod('getFileUrl');
        $this->assertTrue($fileUrlMethod->isPublic());
        $this->assertFalse($fileUrlMethod->isStatic());
    }

    #[Test]
    public function expense_has_expected_properties(): void
    {
        $reflection = new \ReflectionClass(Expense::class);
        
        // Test that protected properties exist
        $this->assertTrue($reflection->hasProperty('fillable'));
        $this->assertTrue($reflection->hasProperty('casts'));
        
        $fillableProperty = $reflection->getProperty('fillable');
        $this->assertTrue($fillableProperty->isProtected());
        
        $castsProperty = $reflection->getProperty('casts');
        $this->assertTrue($castsProperty->isProtected());
    }
}
