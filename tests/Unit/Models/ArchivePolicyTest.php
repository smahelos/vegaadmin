<?php

namespace Tests\Unit\Models;

use App\Models\ArchivePolicy;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ArchivePolicy Model - CRITICAL RULE: Only pure business logic, no Laravel dependencies
 * 
 * According to Unit Test Isolation rule, this class tests only:
 * - Class structure and inheritance
 * - Method signatures and return types
 * - Trait usage and class constants
 * - Class introspection without executing Laravel-dependent methods
 * 
 * Database relationships and model behavior have been moved to Feature tests.
 */
class ArchivePolicyTest extends TestCase
{
    private ArchivePolicy $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new ArchivePolicy();
    }

    #[Test]
    public function model_extends_eloquent_model(): void
    {
        $this->assertInstanceOf(Model::class, $this->model);
    }

    #[Test]
    public function model_uses_crud_trait(): void
    {
        $this->assertContains(CrudTrait::class, class_uses($this->model));
    }

    #[Test]
    public function model_uses_has_factory_trait(): void
    {
        $this->assertContains(HasFactory::class, class_uses($this->model));
    }

    #[Test]
    public function model_has_correct_fillable_properties(): void
    {
        $reflection = new \ReflectionClass($this->model);
        $property = $reflection->getProperty('fillable');
        
        $this->assertTrue($property->isProtected());
        $this->assertIsArray($this->model->getFillable());
    }

    #[Test]
    public function model_has_correct_casts_properties(): void
    {
        $reflection = new \ReflectionClass($this->model);
        $property = $reflection->getProperty('casts');
        
        $this->assertTrue($property->isProtected());
        $this->assertIsArray($this->model->getCasts());
    }

    #[Test]
    public function get_enabled_badge_attribute_method_exists(): void
    {
        $reflection = new \ReflectionClass($this->model);
        $method = $reflection->getMethod('getEnabledBadgeAttribute');
        
        $this->assertTrue($method->isPublic());
        $this->assertCount(0, $method->getParameters());
    }

    #[Test]
    public function get_retention_period_attribute_method_exists(): void
    {
        $reflection = new \ReflectionClass($this->model);
        $method = $reflection->getMethod('getRetentionPeriodAttribute');
        
        $this->assertTrue($method->isPublic());
        $this->assertCount(0, $method->getParameters());
    }

    #[Test]
    public function get_last_archived_formatted_attribute_method_exists(): void
    {
        $reflection = new \ReflectionClass($this->model);
        $method = $reflection->getMethod('getLastArchivedFormattedAttribute');
        
        $this->assertTrue($method->isPublic());
        $this->assertCount(0, $method->getParameters());
    }

    #[Test]
    public function get_records_to_archive_attribute_method_exists(): void
    {
        $reflection = new \ReflectionClass($this->model);
        $method = $reflection->getMethod('getRecordsToArchiveAttribute');
        
        $this->assertTrue($method->isPublic());
        $this->assertCount(0, $method->getParameters());
    }

    #[Test]
    public function scope_enabled_method_exists(): void
    {
        $reflection = new \ReflectionClass($this->model);
        $method = $reflection->getMethod('scopeEnabled');
        
        $this->assertTrue($method->isPublic());
        $this->assertCount(1, $method->getParameters());
    }

    #[Test]
    public function model_has_expected_namespace(): void
    {
        $reflection = new \ReflectionClass($this->model);
        $this->assertEquals('App\Models', $reflection->getNamespaceName());
    }

    #[Test]
    public function model_has_all_required_methods(): void
    {
        $reflection = new \ReflectionClass($this->model);
        
        $this->assertTrue($reflection->hasMethod('getEnabledBadgeAttribute'));
        $this->assertTrue($reflection->hasMethod('getRetentionPeriodAttribute'));
        $this->assertTrue($reflection->hasMethod('getLastArchivedFormattedAttribute'));
        $this->assertTrue($reflection->hasMethod('getRecordsToArchiveAttribute'));
        $this->assertTrue($reflection->hasMethod('scopeEnabled'));
    }

    #[Test]
    public function model_uses_expected_traits(): void
    {
        $traits = class_uses($this->model);
        
        $this->assertContains(CrudTrait::class, $traits);
        $this->assertContains(HasFactory::class, $traits);
    }
}
