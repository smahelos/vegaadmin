<?php

namespace Tests\Unit\Models;

use App\Models\PerformanceMetric;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PerformanceMetricTest extends TestCase
{
    private PerformanceMetric $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new PerformanceMetric();
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
    public function model_has_correct_fillable_attributes(): void
    {
        $expectedFillable = [
            'metric_type',
            'table_name',
            'query_type',
            'metric_value',
            'metric_unit',
            'metadata',
            'measured_at'
        ];

        $this->assertEquals($expectedFillable, $this->model->getFillable());
    }

    #[Test]
    public function model_has_correct_casts(): void
    {
        $expectedCasts = [
            'metadata' => 'array',
            'measured_at' => 'datetime',
            'metric_value' => 'decimal:4'
        ];

        foreach ($expectedCasts as $attribute => $cast) {
            $this->assertEquals($cast, $this->model->getCasts()[$attribute]);
        }
    }

    #[Test]
    public function get_formatted_value_attribute_formats_correctly(): void
    {
        $this->model->metric_value = 123.456;
        $this->model->metric_unit = 'ms';
        
        $expected = '123.46 ms';
        $this->assertEquals($expected, $this->model->getFormattedValueAttribute());
    }

    #[Test]
    public function get_formatted_value_attribute_handles_integers(): void
    {
        $this->model->metric_value = 100;
        $this->model->metric_unit = 'MB';
        
        $expected = '100.00 MB';
        $this->assertEquals($expected, $this->model->getFormattedValueAttribute());
    }

    #[Test]
    public function get_metric_type_formatted_attribute_formats_underscored_type(): void
    {
        $this->model->metric_type = 'query_time';
        
        $expected = 'Query time';
        $this->assertEquals($expected, $this->model->getMetricTypeFormattedAttribute());
    }

    #[Test]
    public function get_metric_type_formatted_attribute_capitalizes_simple_type(): void
    {
        $this->model->metric_type = 'performance';
        
        $expected = 'Performance';
        $this->assertEquals($expected, $this->model->getMetricTypeFormattedAttribute());
    }

    #[Test]
    public function scope_of_type_method_exists(): void
    {
        $this->assertTrue(method_exists($this->model, 'scopeOfType'));
    }

    #[Test]
    public function scope_for_table_method_exists(): void
    {
        $this->assertTrue(method_exists($this->model, 'scopeForTable'));
    }

    #[Test]
    public function scope_recent_method_exists(): void
    {
        $this->assertTrue(method_exists($this->model, 'scopeRecent'));
    }

    #[Test]
    public function model_has_correct_table_name(): void
    {
        $this->assertEquals('performance_metrics', $this->model->getTable());
    }

    #[Test]
    public function model_has_correct_primary_key(): void
    {
        $this->assertEquals('id', $this->model->getKeyName());
    }

    #[Test]
    public function model_uses_timestamps(): void
    {
        $this->assertTrue($this->model->usesTimestamps());
    }
}
