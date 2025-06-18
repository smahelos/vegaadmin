<?php

namespace Tests\Unit\Models;

use App\Models\ProductCategory;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductCategoryTest extends TestCase
{
    private ProductCategory $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new ProductCategory();
    }

    #[Test]
    public function model_extends_eloquent_model(): void
    {
        $this->assertInstanceOf(Model::class, $this->model);
    }

    #[Test]
    public function model_uses_has_factory_trait(): void
    {
        $this->assertArrayHasKey(HasFactory::class, class_uses($this->model));
    }

    #[Test]
    public function model_uses_sluggable_trait(): void
    {
        $this->assertArrayHasKey(Sluggable::class, class_uses($this->model));
    }

    #[Test]
    public function model_uses_crud_trait(): void
    {
        $this->assertArrayHasKey(CrudTrait::class, class_uses($this->model));
    }

    #[Test]
    public function table_name_is_product_categories(): void
    {
        $this->assertEquals('product_categories', $this->model->getTable());
    }

    #[Test]
    public function fillable_attributes_are_properly_defined(): void
    {
        $expectedFillable = [
            'name',
            'slug',
            'description',
        ];

        $this->assertEquals($expectedFillable, $this->model->getFillable());
    }

    #[Test]
    public function sluggable_method_exists(): void
    {
        $this->assertTrue(method_exists($this->model, 'sluggable'));
    }

    #[Test]
    public function products_relationship_method_exists(): void
    {
        $this->assertTrue(method_exists($this->model, 'products'));
    }

    #[Test]
    public function sluggable_configuration_is_correct(): void
    {
        $config = $this->model->sluggable();
        
        $this->assertIsArray($config);
        $this->assertArrayHasKey('slug', $config);
        $this->assertArrayHasKey('source', $config['slug']);
        $this->assertEquals('name', $config['slug']['source']);
    }
}
