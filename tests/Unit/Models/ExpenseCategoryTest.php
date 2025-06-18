<?php

namespace Tests\Unit\Models;

use App\Models\ExpenseCategory;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExpenseCategoryTest extends TestCase
{
    private ExpenseCategory $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new ExpenseCategory();
    }

    #[Test]
    public function model_extends_eloquent_model(): void
    {
        $this->assertInstanceOf(Model::class, $this->model);
    }

    #[Test]
    public function model_uses_crud_trait(): void
    {
        $this->assertArrayHasKey(CrudTrait::class, class_uses($this->model));
    }

    #[Test]
    public function model_uses_has_factory_trait(): void
    {
        $this->assertArrayHasKey(HasFactory::class, class_uses($this->model));
    }

    #[Test]
    public function table_name_is_expense_categories(): void
    {
        $this->assertEquals('expense_categories', $this->model->getTable());
    }

    #[Test]
    public function fillable_attributes_are_properly_defined(): void
    {
        $expectedFillable = [
            'name',
            'slug',
            'description',
            'color',
            'is_active'
        ];

        $this->assertEquals($expectedFillable, $this->model->getFillable());
    }

    #[Test]
    public function casts_are_properly_defined(): void
    {
        $expectedCasts = [
            'is_active' => 'boolean',
        ];

        foreach ($expectedCasts as $attribute => $expectedCast) {
            $this->assertEquals($expectedCast, $this->model->getCasts()[$attribute]);
        }
    }

    #[Test]
    public function expenses_relationship_method_exists(): void
    {
        $this->assertTrue(method_exists($this->model, 'expenses'));
    }
}
