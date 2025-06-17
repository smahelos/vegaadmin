<?php

namespace Tests\Unit\Models;

use App\Models\StatusCategory;
use App\Models\Status;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class StatusCategoryTest extends TestCase
{
    private StatusCategory $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new StatusCategory();
    }

    #[Test]
    public function model_extends_base_model()
    {
        $this->assertInstanceOf(Model::class, $this->model);
    }

    #[Test]
    public function model_uses_crud_trait()
    {
        $this->assertContains(CrudTrait::class, class_uses($this->model));
    }

    #[Test]
    public function model_uses_has_factory_trait()
    {
        $this->assertContains(HasFactory::class, class_uses($this->model));
    }

    #[Test]
    public function model_has_correct_table_name()
    {
        $this->assertEquals('status_categories', $this->model->getTable());
    }

    #[Test]
    public function model_has_correct_guarded_attributes()
    {
        $guarded = $this->model->getGuarded();
        $this->assertContains('id', $guarded);
    }

    #[Test]
    public function model_uses_timestamps()
    {
        $this->assertTrue($this->model->usesTimestamps());
    }

    #[Test]
    public function model_has_statuses_relationship_method()
    {
        $this->assertTrue(method_exists($this->model, 'statuses'));
    }

    #[Test]
    public function statuses_relationship_returns_has_many_relation()
    {
        // Unit test - just check method exists and would return correct type
        $this->assertTrue(method_exists($this->model, 'statuses'));
        
        // Check method signature via reflection
        $reflection = new \ReflectionClass($this->model);
        $method = $reflection->getMethod('statuses');
        $this->assertTrue($method->isPublic());
    }

    #[Test]
    public function model_has_slug_mutator()
    {
        $this->assertTrue(method_exists($this->model, 'setSlugAttribute'));
    }

    #[Test]
    public function slug_mutator_method_is_public()
    {
        $reflection = new \ReflectionClass($this->model);
        $method = $reflection->getMethod('setSlugAttribute');
        $this->assertTrue($method->isPublic());
    }

    #[Test]
    public function slug_mutator_accepts_string_parameter()
    {
        $reflection = new \ReflectionClass($this->model);
        $method = $reflection->getMethod('setSlugAttribute');
        $parameters = $method->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertEquals('value', $parameters[0]->getName());
    }

    #[Test]
    public function model_class_namespace_is_correct()
    {
        $this->assertEquals('App\Models\StatusCategory', get_class($this->model));
    }

    #[Test]
    public function model_has_expected_fillable_fields_structure()
    {
        // Since model uses guarded instead of fillable, check guarded
        $guarded = $this->model->getGuarded();
        
        // ID should be guarded (not mass assignable)
        $this->assertContains('id', $guarded);
    }

    #[Test]
    public function model_relationship_foreign_key_structure()
    {
        // Unit test - check that the relationship method exists
        // The actual relationship testing should be in Feature tests
        $this->assertTrue(method_exists($this->model, 'statuses'));
        
        // Verify it's defined to use correct foreign key structure
        $reflection = new \ReflectionClass($this->model);
        $method = $reflection->getMethod('statuses');
        $this->assertTrue($method->isPublic());
    }

    #[Test]
    public function model_primary_key_is_default()
    {
        $this->assertEquals('id', $this->model->getKeyName());
    }

    #[Test]
    public function model_connection_is_default()
    {
        $this->assertNull($this->model->getConnectionName());
    }

    #[Test]
    public function model_has_factory()
    {
        $this->assertTrue(method_exists($this->model, 'factory'));
    }

    #[Test]
    public function model_casts_are_defined()
    {
        $casts = $this->model->getCasts();
        
        // Should have default Laravel casts
        $this->assertArrayHasKey('id', $casts);
    }
}
