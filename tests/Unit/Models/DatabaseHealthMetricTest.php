<?php

namespace Tests\Unit\Models;

use App\Models\DatabaseHealthMetric;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for DatabaseHealthMetric model - CRITICAL RULE: Only pure business logic, no Laravel dependencies
 * 
 * According to Unit Test Isolation rule, this class tests only:
 * - Class structure and traits (without instantiation)
 * - Static methods and pure calculations (if any)
 * - Class constants and basic reflection
 * 
 * All Eloquent-dependent tests (fillable, casts, table name, scopes, accessors)
 * have been moved to Feature tests.
 */
class DatabaseHealthMetricTest extends TestCase
{
    #[Test]
    public function database_health_metric_uses_expected_traits(): void
    {
        // Test traits on the actual class without instantiation
        $traits = class_uses_recursive(DatabaseHealthMetric::class);
        
        $expectedTraits = [
            'Illuminate\Database\Eloquent\Factories\HasFactory',
            'Backpack\CRUD\app\Models\Traits\CrudTrait',
        ];
        
        foreach ($expectedTraits as $trait) {
            $this->assertContains($trait, $traits, "DatabaseHealthMetric model should use {$trait} trait");
        }
    }

    #[Test]
    public function database_health_metric_class_exists_and_is_instantiable(): void
    {
        $this->assertTrue(class_exists(DatabaseHealthMetric::class));
        
        $reflection = new \ReflectionClass(DatabaseHealthMetric::class);
        $this->assertTrue($reflection->isInstantiable());
    }

    #[Test]
    public function database_health_metric_extends_correct_parent_class(): void
    {
        $reflection = new \ReflectionClass(DatabaseHealthMetric::class);
        $this->assertEquals('Illuminate\Database\Eloquent\Model', $reflection->getParentClass()->getName());
    }

    #[Test]
    public function database_health_metric_has_expected_class_structure(): void
    {
        $reflection = new \ReflectionClass(DatabaseHealthMetric::class);
        
        // Test that class is not abstract or interface
        $this->assertFalse($reflection->isAbstract());
        $this->assertFalse($reflection->isInterface());
        $this->assertTrue($reflection->isInstantiable());
        
        // Test namespace
        $this->assertEquals('App\Models', $reflection->getNamespaceName());
    }

    #[Test]
    public function database_health_metric_has_class_constants(): void
    {
        $reflection = new \ReflectionClass(DatabaseHealthMetric::class);
        $constants = $reflection->getConstants();
        
        // This model doesn't define custom constants, but the test structure is here for future use
        $this->assertIsArray($constants);
    }

    #[Test]
    public function database_health_metric_has_expected_public_methods(): void
    {
        $reflection = new \ReflectionClass(DatabaseHealthMetric::class);
        
        // Test for essential method existence without calling them
        $this->assertTrue($reflection->hasMethod('getFillable'));
        $this->assertTrue($reflection->hasMethod('getCasts'));
        $this->assertTrue($reflection->hasMethod('getTable'));
        
        // Test for accessor methods
        $this->assertTrue($reflection->hasMethod('getStatusBadgeAttribute'));
        $this->assertTrue($reflection->hasMethod('getFormattedValueAttribute'));
        
        // Test for scope methods
        $this->assertTrue($reflection->hasMethod('scopeRecent'));
        $this->assertTrue($reflection->hasMethod('scopeStatus'));
        $this->assertTrue($reflection->hasMethod('scopeMetricType'));
    }

    #[Test]
    public function accessor_methods_exist_and_are_public(): void
    {
        $reflection = new \ReflectionClass(DatabaseHealthMetric::class);
        
        // Test status badge accessor method
        $statusBadgeMethod = $reflection->getMethod('getStatusBadgeAttribute');
        $this->assertTrue($statusBadgeMethod->isPublic());
        $this->assertFalse($statusBadgeMethod->isStatic());
        $this->assertEquals(0, $statusBadgeMethod->getNumberOfParameters());
        
        // Test formatted value accessor method
        $formattedValueMethod = $reflection->getMethod('getFormattedValueAttribute');
        $this->assertTrue($formattedValueMethod->isPublic());
        $this->assertFalse($formattedValueMethod->isStatic());
        $this->assertEquals(0, $formattedValueMethod->getNumberOfParameters());
    }

    #[Test]
    public function scope_methods_exist_and_are_public(): void
    {
        $reflection = new \ReflectionClass(DatabaseHealthMetric::class);
        
        $scopeMethods = ['scopeRecent', 'scopeStatus', 'scopeMetricType'];
        
        foreach ($scopeMethods as $methodName) {
            $method = $reflection->getMethod($methodName);
            $this->assertTrue($method->isPublic(), "{$methodName} method should be public");
            $this->assertFalse($method->isStatic(), "{$methodName} method should not be static");
            
            // Scope methods should have at least 1 parameter (the query builder)
            $this->assertGreaterThanOrEqual(1, $method->getNumberOfParameters());
        }
    }

    #[Test]
    public function database_health_metric_has_expected_properties(): void
    {
        $reflection = new \ReflectionClass(DatabaseHealthMetric::class);
        
        // Test that protected properties exist
        $this->assertTrue($reflection->hasProperty('table'));
        $this->assertTrue($reflection->hasProperty('fillable'));
        $this->assertTrue($reflection->hasProperty('casts'));
        
        $tableProperty = $reflection->getProperty('table');
        $this->assertTrue($tableProperty->isProtected());
        
        $fillableProperty = $reflection->getProperty('fillable');
        $this->assertTrue($fillableProperty->isProtected());
        
        $castsProperty = $reflection->getProperty('casts');
        $this->assertTrue($castsProperty->isProtected());
    }

    #[Test]
    public function scope_status_method_has_correct_signature(): void
    {
        $reflection = new \ReflectionClass(DatabaseHealthMetric::class);
        $method = $reflection->getMethod('scopeStatus');
        
        $this->assertEquals(2, $method->getNumberOfParameters());
        $parameters = $method->getParameters();
        $this->assertEquals('query', $parameters[0]->getName());
        $this->assertEquals('status', $parameters[1]->getName());
    }

    #[Test]
    public function scope_metric_type_method_has_correct_signature(): void
    {
        $reflection = new \ReflectionClass(DatabaseHealthMetric::class);
        $method = $reflection->getMethod('scopeMetricType');
        
        $this->assertEquals(2, $method->getNumberOfParameters());
        $parameters = $method->getParameters();
        $this->assertEquals('query', $parameters[0]->getName());
        $this->assertEquals('metricName', $parameters[1]->getName());
    }
}
