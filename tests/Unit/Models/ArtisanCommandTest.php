<?php

namespace Tests\Unit\Models;

use App\Models\ArtisanCommand;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ArtisanCommand Model - CRITICAL RULE: Only pure business logic, no Laravel dependencies
 * 
 * According to Unit Test Isolation rule, this class tests only:
 * - Class structure and inheritance
 * - Method signatures and return types
 * - Trait usage and class constants
 * - Class introspection without executing Laravel-dependent methods
 * 
 * Database relationships and model behavior have been moved to Feature tests.
 */
class ArtisanCommandTest extends TestCase
{
    private ArtisanCommand $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new ArtisanCommand();
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
    public function category_method_exists(): void
    {
        $reflection = new \ReflectionClass($this->model);
        $method = $reflection->getMethod('category');
        
        $this->assertTrue($method->isPublic());
        $this->assertCount(0, $method->getParameters());
    }

    #[Test]
    public function category_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass($this->model);
        $method = $reflection->getMethod('category');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('Illuminate\Database\Eloquent\Relations\BelongsTo', $returnType->getName());
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
        
        $this->assertTrue($reflection->hasMethod('category'));
    }

    #[Test]
    public function model_uses_expected_traits(): void
    {
        $traits = class_uses($this->model);
        
        $this->assertContains(CrudTrait::class, $traits);
        $this->assertContains(HasFactory::class, $traits);
    }

    #[Test]
    public function fillable_property_contains_expected_fields(): void
    {
        $fillable = $this->model->getFillable();
        
        $expectedFields = [
            'name',
            'description',
            'category_id',
            'is_active',
            'command',
            'parameters_description',
            'sort_order',
        ];
        
        foreach ($expectedFields as $field) {
            $this->assertContains($field, $fillable);
        }
    }

    #[Test]
    public function casts_property_contains_expected_casts(): void
    {
        $casts = $this->model->getCasts();
        
        $this->assertArrayHasKey('is_active', $casts);
        $this->assertEquals('boolean', $casts['is_active']);
        
        $this->assertArrayHasKey('sort_order', $casts);
        $this->assertEquals('integer', $casts['sort_order']);
    }
}
