<?php

namespace Tests\Unit\Models;

use App\Models\DatabaseMaintenanceLog;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DatabaseMaintenanceLogTest extends TestCase
{
    private DatabaseMaintenanceLog $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new DatabaseMaintenanceLog();
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
            'task_type',
            'table_name',
            'status',
            'description',
            'results',
            'started_at',
            'completed_at'
        ];

        $this->assertEquals($expectedFillable, $this->model->getFillable());
    }

    #[Test]
    public function model_has_correct_casts(): void
    {
        $expectedCasts = [
            'results' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime'
        ];

        foreach ($expectedCasts as $attribute => $cast) {
            $this->assertEquals($cast, $this->model->getCasts()[$attribute]);
        }
    }

    #[Test]
    public function get_duration_attribute_returns_null_when_no_dates(): void
    {
        $this->assertNull($this->model->getDurationAttribute());
    }

    #[Test]
    public function get_duration_attribute_returns_null_when_only_started_at(): void
    {
        $this->model->started_at = now();
        
        $this->assertNull($this->model->getDurationAttribute());
    }

    #[Test]
    public function get_duration_attribute_returns_null_when_only_completed_at(): void
    {
        $this->model->completed_at = now();
        
        $this->assertNull($this->model->getDurationAttribute());
    }

    #[Test]
    public function get_status_badge_attribute_returns_correct_badge_for_pending(): void
    {
        $this->model->status = 'pending';
        
        $expected = '<span class="badge badge-secondary">Pending</span>';
        $this->assertEquals($expected, $this->model->getStatusBadgeAttribute());
    }

    #[Test]
    public function get_status_badge_attribute_returns_correct_badge_for_running(): void
    {
        $this->model->status = 'running';
        
        $expected = '<span class="badge badge-warning">Running</span>';
        $this->assertEquals($expected, $this->model->getStatusBadgeAttribute());
    }

    #[Test]
    public function get_status_badge_attribute_returns_correct_badge_for_completed(): void
    {
        $this->model->status = 'completed';
        
        $expected = '<span class="badge badge-success">Completed</span>';
        $this->assertEquals($expected, $this->model->getStatusBadgeAttribute());
    }

    #[Test]
    public function get_status_badge_attribute_returns_correct_badge_for_failed(): void
    {
        $this->model->status = 'failed';
        
        $expected = '<span class="badge badge-danger">Failed</span>';
        $this->assertEquals($expected, $this->model->getStatusBadgeAttribute());
    }

    #[Test]
    public function get_status_badge_attribute_returns_unknown_badge_for_invalid_status(): void
    {
        $this->model->status = 'invalid_status';
        
        $expected = '<span class="badge badge-light">Unknown</span>';
        $this->assertEquals($expected, $this->model->getStatusBadgeAttribute());
    }

    #[Test]
    public function get_task_type_formatted_attribute_formats_underscored_task_type(): void
    {
        $this->model->task_type = 'table_optimization';
        
        $expected = 'Table optimization';
        $this->assertEquals($expected, $this->model->getTaskTypeFormattedAttribute());
    }

    #[Test]
    public function get_task_type_formatted_attribute_capitalizes_simple_task_type(): void
    {
        $this->model->task_type = 'analyze';
        
        $expected = 'Analyze';
        $this->assertEquals($expected, $this->model->getTaskTypeFormattedAttribute());
    }

    #[Test]
    public function model_table_name_is_correct(): void
    {
        $this->assertEquals('database_maintenance_logs', $this->model->getTable());
    }
}
