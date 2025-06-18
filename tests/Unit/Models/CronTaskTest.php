<?php

namespace Tests\Unit\Models;

use App\Models\CronTask;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CronTask model - CRITICAL RULE: Only pure business logic, no Laravel dependencies
 * 
 * According to Unit Test Isolation rule, this class tests only:
 * - Class structure and traits (without instantiation)
 * - Static methods and pure calculations (if any)
 * - Class constants and basic reflection
 * 
 * All Eloquent-dependent tests (fillable, casts, accessors, mutators, business logic methods)
 * have been moved to Feature tests.
 */
class CronTaskTest extends TestCase
{
    #[Test]
    public function cron_task_uses_expected_traits(): void
    {
        // Test traits on the actual class without instantiation
        $traits = class_uses_recursive(CronTask::class);
        
        $expectedTraits = [
            'Illuminate\Database\Eloquent\Factories\HasFactory',
            'Backpack\CRUD\app\Models\Traits\CrudTrait',
        ];
        
        foreach ($expectedTraits as $trait) {
            $this->assertContains($trait, $traits, "CronTask model should use {$trait} trait");
        }
    }

    #[Test]
    public function cron_task_class_exists_and_is_instantiable(): void
    {
        $this->assertTrue(class_exists(CronTask::class));
        
        $reflection = new \ReflectionClass(CronTask::class);
        $this->assertTrue($reflection->isInstantiable());
    }

    #[Test]
    public function cron_task_extends_correct_parent_class(): void
    {
        $reflection = new \ReflectionClass(CronTask::class);
        $this->assertEquals('Illuminate\Database\Eloquent\Model', $reflection->getParentClass()->getName());
    }

    #[Test]
    public function cron_task_has_expected_class_structure(): void
    {
        $reflection = new \ReflectionClass(CronTask::class);
        
        // Test that class is not abstract or interface
        $this->assertFalse($reflection->isAbstract());
        $this->assertFalse($reflection->isInterface());
        $this->assertTrue($reflection->isInstantiable());
        
        // Test namespace
        $this->assertEquals('App\Models', $reflection->getNamespaceName());
    }

    #[Test]
    public function cron_task_has_class_constants(): void
    {
        $reflection = new \ReflectionClass(CronTask::class);
        $constants = $reflection->getConstants();
        
        // This model doesn't define custom constants, but the test structure is here for future use
        $this->assertIsArray($constants);
    }

    #[Test]
    public function cron_task_has_expected_public_methods(): void
    {
        $reflection = new \ReflectionClass(CronTask::class);
        
        // Test for essential method existence without calling them
        $this->assertTrue($reflection->hasMethod('getFillable'));
        $this->assertTrue($reflection->hasMethod('getCasts'));
        $this->assertTrue($reflection->hasMethod('getTable'));
        
        // Test for accessor methods
        $this->assertTrue($reflection->hasMethod('getFrequencyNameAttribute'));
        $this->assertTrue($reflection->hasMethod('getDayOfWeekNameAttribute'));
        $this->assertTrue($reflection->hasMethod('getFormattedRunAtAttribute'));
        $this->assertTrue($reflection->hasMethod('getBaseCommandAttribute'));
        $this->assertTrue($reflection->hasMethod('getCommandParamsAttribute'));
        
        // Test for mutator methods  
        $this->assertTrue($reflection->hasMethod('setCommandAttribute'));
        
        // Test for business logic methods
        $this->assertTrue($reflection->hasMethod('getCronExpression'));
        $this->assertTrue($reflection->hasMethod('getNextRunDate'));
        $this->assertTrue($reflection->hasMethod('simulateRun'));
    }

    #[Test]
    public function accessor_methods_exist_and_are_public(): void
    {
        $reflection = new \ReflectionClass(CronTask::class);
        
        $accessorMethods = [
            'getFrequencyNameAttribute',
            'getDayOfWeekNameAttribute', 
            'getFormattedRunAtAttribute',
            'getBaseCommandAttribute',
            'getCommandParamsAttribute'
        ];
        
        foreach ($accessorMethods as $methodName) {
            $method = $reflection->getMethod($methodName);
            $this->assertTrue($method->isPublic(), "{$methodName} method should be public");
            $this->assertFalse($method->isStatic(), "{$methodName} method should not be static");
            $this->assertEquals(0, $method->getNumberOfParameters(), "{$methodName} should have 0 parameters");
        }
    }

    #[Test]
    public function mutator_methods_exist_and_are_public(): void
    {
        $reflection = new \ReflectionClass(CronTask::class);
        
        // Test setCommandAttribute method
        $setCommandMethod = $reflection->getMethod('setCommandAttribute');
        $this->assertTrue($setCommandMethod->isPublic());
        $this->assertFalse($setCommandMethod->isStatic());
        $this->assertEquals(1, $setCommandMethod->getNumberOfParameters());
    }

    #[Test]
    public function business_logic_methods_exist_and_are_public(): void
    {
        $reflection = new \ReflectionClass(CronTask::class);
        
        // Test getCronExpression method
        $cronExpressionMethod = $reflection->getMethod('getCronExpression');
        $this->assertTrue($cronExpressionMethod->isPublic());
        $this->assertFalse($cronExpressionMethod->isStatic());
        $this->assertEquals(0, $cronExpressionMethod->getNumberOfParameters());
        
        // Test getNextRunDate method
        $nextRunDateMethod = $reflection->getMethod('getNextRunDate');
        $this->assertTrue($nextRunDateMethod->isPublic());
        $this->assertFalse($nextRunDateMethod->isStatic());
        $this->assertEquals(0, $nextRunDateMethod->getNumberOfParameters());
        
        // Test simulateRun method
        $simulateRunMethod = $reflection->getMethod('simulateRun');
        $this->assertTrue($simulateRunMethod->isPublic());
        $this->assertFalse($simulateRunMethod->isStatic());
        $this->assertEquals(0, $simulateRunMethod->getNumberOfParameters());
    }

    #[Test]
    public function get_cron_expression_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass(CronTask::class);
        $method = $reflection->getMethod('getCronExpression');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('string', $returnType->getName());
        $this->assertFalse($returnType->allowsNull());
    }

    #[Test]
    public function get_next_run_date_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass(CronTask::class);
        $method = $reflection->getMethod('getNextRunDate');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('Carbon\Carbon', $returnType->getName());
        $this->assertTrue($returnType->allowsNull());
    }

    #[Test]
    public function cron_task_has_expected_properties(): void
    {
        $reflection = new \ReflectionClass(CronTask::class);
        
        // Test that protected properties exist
        $this->assertTrue($reflection->hasProperty('fillable'));
        $this->assertTrue($reflection->hasProperty('casts'));
        
        $fillableProperty = $reflection->getProperty('fillable');
        $this->assertTrue($fillableProperty->isProtected());
        
        $castsProperty = $reflection->getProperty('casts');
        $this->assertTrue($castsProperty->isProtected());
    }
}
