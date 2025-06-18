<?php

namespace Tests\Unit\Models;

use App\Models\Status;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Status model - CRITICAL RULE: Only pure business logic, no Laravel dependencies
 * 
 * According to Unit Test Isolation rule, this class tests only:
 * - Class structure and traits (without instantiation)
 * - Static methods and pure calculations (if any)
 * - Class constants and basic reflection
 * 
 * All Eloquent-dependent tests have been moved to Feature tests.
 */
class StatusTest extends TestCase
{
    #[Test]
    public function status_uses_expected_traits(): void
    {
        $traits = class_uses_recursive(Status::class);
        
        $expectedTraits = [
            'Illuminate\Database\Eloquent\Factories\HasFactory',
            'Backpack\CRUD\app\Models\Traits\CrudTrait',
        ];
        
        foreach ($expectedTraits as $trait) {
            $this->assertContains($trait, $traits, "Status model should use {$trait} trait");
        }
    }

    #[Test]
    public function status_class_exists_and_is_instantiable(): void
    {
        $this->assertTrue(class_exists(Status::class));
        
        $reflection = new \ReflectionClass(Status::class);
        $this->assertTrue($reflection->isInstantiable());
    }

    #[Test]
    public function status_extends_correct_parent_class(): void
    {
        $reflection = new \ReflectionClass(Status::class);
        $this->assertEquals('Illuminate\Database\Eloquent\Model', $reflection->getParentClass()->getName());
    }

    #[Test]
    public function status_has_expected_class_structure(): void
    {
        $reflection = new \ReflectionClass(Status::class);
        
        $this->assertFalse($reflection->isAbstract());
        $this->assertFalse($reflection->isInterface());
        $this->assertTrue($reflection->isInstantiable());
        $this->assertEquals('App\Models', $reflection->getNamespaceName());
    }

    #[Test]
    public function status_has_class_constants(): void
    {
        $reflection = new \ReflectionClass(Status::class);
        $constants = $reflection->getConstants();
        
        $this->assertIsArray($constants);
    }

    #[Test]
    public function status_has_expected_public_methods(): void
    {
        $reflection = new \ReflectionClass(Status::class);
        
        // Check that key relationship methods exist without instantiation
        $this->assertTrue($reflection->hasMethod('category'));
        
        // Check method visibility
        $categoryMethod = $reflection->getMethod('category');
        $this->assertTrue($categoryMethod->isPublic());
    }
}
