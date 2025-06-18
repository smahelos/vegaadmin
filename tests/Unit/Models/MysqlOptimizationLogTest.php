<?php

namespace Tests\Unit\Models;

use App\Models\MysqlOptimizationLog;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MysqlOptimizationLogTest extends TestCase
{
    private MysqlOptimizationLog $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new MysqlOptimizationLog();
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
    public function model_has_correct_table_name(): void
    {
        $this->assertEquals('mysql_optimization_logs', $this->model->getTable());
    }

    #[Test]
    public function model_has_correct_fillable_attributes(): void
    {
        $expectedFillable = [
            'setting_name',
            'current_value',
            'recommended_value',
            'description',
            'priority',
            'applied'
        ];

        $this->assertEquals($expectedFillable, $this->model->getFillable());
    }

    #[Test]
    public function model_has_correct_casts(): void
    {
        $expectedCasts = [
            'applied' => 'boolean'
        ];

        foreach ($expectedCasts as $attribute => $cast) {
            $this->assertEquals($cast, $this->model->getCasts()[$attribute]);
        }
    }

    #[Test]
    public function get_priority_badge_attribute_returns_correct_badge_for_high(): void
    {
        $this->model->priority = 'high';
        
        $expected = '<span class="badge badge-danger">High</span>';
        $this->assertEquals($expected, $this->model->getPriorityBadgeAttribute());
    }

    #[Test]
    public function get_priority_badge_attribute_returns_correct_badge_for_medium(): void
    {
        $this->model->priority = 'medium';
        
        $expected = '<span class="badge badge-warning">Medium</span>';
        $this->assertEquals($expected, $this->model->getPriorityBadgeAttribute());
    }

    #[Test]
    public function get_priority_badge_attribute_returns_correct_badge_for_low(): void
    {
        $this->model->priority = 'low';
        
        $expected = '<span class="badge badge-info">Low</span>';
        $this->assertEquals($expected, $this->model->getPriorityBadgeAttribute());
    }

    #[Test]
    public function get_priority_badge_attribute_returns_unknown_badge_for_invalid_priority(): void
    {
        $this->model->priority = 'invalid_priority';
        
        $expected = '<span class="badge badge-light">Unknown</span>';
        $this->assertEquals($expected, $this->model->getPriorityBadgeAttribute());
    }

    #[Test]
    public function get_applied_badge_attribute_returns_applied_badge_when_true(): void
    {
        $this->model->applied = true;
        
        $expected = '<span class="badge badge-success">Applied</span>';
        $this->assertEquals($expected, $this->model->getAppliedBadgeAttribute());
    }

    #[Test]
    public function get_applied_badge_attribute_returns_pending_badge_when_false(): void
    {
        $this->model->applied = false;
        
        $expected = '<span class="badge badge-secondary">Pending</span>';
        $this->assertEquals($expected, $this->model->getAppliedBadgeAttribute());
    }

    #[Test]
    public function get_current_value_from_db_attribute_returns_current_value_when_set(): void
    {
        $this->model->current_value = '256M';
        
        $this->assertEquals('256M', $this->model->getCurrentValueFromDbAttribute());
    }

    #[Test]
    public function scope_high_priority_method_exists(): void
    {
        $this->assertTrue(method_exists($this->model, 'scopeHighPriority'));
    }

    #[Test]
    public function scope_unapplied_method_exists(): void
    {
        $this->assertTrue(method_exists($this->model, 'scopeUnapplied'));
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
