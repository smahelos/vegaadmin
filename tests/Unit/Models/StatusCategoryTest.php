<?php

namespace Tests\Unit\Models;

use App\Models\StatusCategory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for StatusCategory model - CRITICAL RULE: Only pure business logic, no Laravel dependencies
 * 
 * According to Unit Test Isolation rule, this class tests only:
 * - Class structure and traits (without instantiation)
 * - Static methods and pure calculations (if any)
 * - Class constants and basic reflection
 * 
 * All Eloquent-dependent tests have been moved to Feature tests.
 */
class StatusCategoryTest extends TestCase
{
    #[Test]
    public function status_category_uses_expected_traits(): void
    {
        $traits = class_uses_recursive(StatusCategory::class);
        
        $expectedTraits = [
            'Illuminate\Database\Eloquent\Factories\HasFactory',
            'Backpack\CRUD\app\Models\Traits\CrudTrait',
        ];
        
        foreach ($expectedTraits as $trait) {
            $this->assertContains($trait, $traits, "StatusCategory model should use {$trait} trait");
        }
    }

    #[Test]
    public function status_category_class_exists_and_is_instantiable(): void
    {
        $this->assertTrue(class_exists(StatusCategory::class));
        
        $reflection = new \ReflectionClass(StatusCategory::class);
        $this->assertTrue($reflection->isInstantiable());
    }

    #[Test]
    public function status_category_extends_correct_parent_class(): void
    {
        $reflection = new \ReflectionClass(StatusCategory::class);
        $this->assertEquals('Illuminate\Database\Eloquent\Model', $reflection->getParentClass()->getName());
    }

    #[Test]
    public function status_category_has_expected_class_structure(): void
    {
        $reflection = new \ReflectionClass(StatusCategory::class);
        
        $this->assertFalse($reflection->isAbstract());
        $this->assertFalse($reflection->isInterface());
        $this->assertTrue($reflection->isInstantiable());
        $this->assertEquals('App\Models', $reflection->getNamespaceName());
    }

    #[Test]
    public function status_category_has_class_constants(): void
    {
        $reflection = new \ReflectionClass(StatusCategory::class);
        $constants = $reflection->getConstants();
        
        $this->assertIsArray($constants);
    }

    #[Test]
    public function status_category_has_expected_public_methods(): void
    {
        $reflection = new \ReflectionClass(StatusCategory::class);
        
        // Check that key methods exist without instantiation
        $this->assertTrue($reflection->hasMethod('statuses'));
        $this->assertTrue($reflection->hasMethod('setSlugAttribute'));
        
        // Check method visibility
        $statusesMethod = $reflection->getMethod('statuses');
        $this->assertTrue($statusesMethod->isPublic());
        
        $slugMethod = $reflection->getMethod('setSlugAttribute');
        $this->assertTrue($slugMethod->isPublic());
    }

    #[Test]
    public function status_category_slug_mutator_has_correct_signature(): void
    {
        $reflection = new \ReflectionClass(StatusCategory::class);
        $method = $reflection->getMethod('setSlugAttribute');
        $parameters = $method->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertEquals('value', $parameters[0]->getName());
    }
}
