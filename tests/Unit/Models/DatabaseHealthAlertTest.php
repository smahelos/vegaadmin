<?php

namespace Tests\Unit\Models;

use App\Models\DatabaseHealthAlert;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DatabaseHealthAlertTest extends TestCase
{
    private DatabaseHealthAlert $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new DatabaseHealthAlert();
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
    public function table_name_is_database_health_alerts(): void
    {
        $this->assertEquals('database_health_alerts', $this->model->getTable());
    }

    #[Test]
    public function fillable_attributes_are_properly_defined(): void
    {
        $expectedFillable = [
            'alert_type',
            'severity',
            'message',
            'metric_data',
            'resolved',
            'resolved_at'
        ];

        $this->assertEquals($expectedFillable, $this->model->getFillable());
    }

    #[Test]
    public function casts_are_properly_defined(): void
    {
        $expectedCasts = [
            'metric_data' => 'array',
            'resolved' => 'boolean',
            'resolved_at' => 'datetime'
        ];

        foreach ($expectedCasts as $attribute => $expectedCast) {
            $this->assertEquals($expectedCast, $this->model->getCasts()[$attribute]);
        }
    }

    #[Test]
    public function get_severity_badge_attribute_method_exists(): void
    {
        $this->assertTrue(method_exists($this->model, 'getSeverityBadgeAttribute'));
    }

    #[Test]
    public function get_resolved_badge_attribute_method_exists(): void
    {
        $this->assertTrue(method_exists($this->model, 'getResolvedBadgeAttribute'));
    }

    #[Test]
    public function scope_unresolved_method_exists(): void
    {
        $this->assertTrue(method_exists($this->model, 'scopeUnresolved'));
    }

    #[Test]
    public function scope_severity_method_exists(): void
    {
        $this->assertTrue(method_exists($this->model, 'scopeSeverity'));
    }

    #[Test]
    public function scope_alert_type_method_exists(): void
    {
        $this->assertTrue(method_exists($this->model, 'scopeAlertType'));
    }

    #[Test]
    public function scope_recent_method_exists(): void
    {
        $this->assertTrue(method_exists($this->model, 'scopeRecent'));
    }

    #[Test]
    public function mark_resolved_method_exists(): void
    {
        $this->assertTrue(method_exists($this->model, 'markResolved'));
    }

    #[Test]
    public function severity_badge_returns_correct_values(): void
    {
        // Test info severity
        $this->model->severity = 'info';
        $this->assertStringContainsString('badge-info', $this->model->severity_badge);
        $this->assertStringContainsString('Info', $this->model->severity_badge);

        // Test warning severity
        $this->model->severity = 'warning';
        $this->assertStringContainsString('badge-warning', $this->model->severity_badge);
        $this->assertStringContainsString('Warning', $this->model->severity_badge);

        // Test critical severity
        $this->model->severity = 'critical';
        $this->assertStringContainsString('badge-danger', $this->model->severity_badge);
        $this->assertStringContainsString('Critical', $this->model->severity_badge);

        // Test unknown severity
        $this->model->severity = 'unknown';
        $this->assertStringContainsString('badge-light', $this->model->severity_badge);
        $this->assertStringContainsString('Unknown', $this->model->severity_badge);
    }

    #[Test]
    public function resolved_badge_returns_correct_values(): void
    {
        // Test resolved status
        $this->model->resolved = true;
        $this->assertStringContainsString('badge-success', $this->model->resolved_badge);
        $this->assertStringContainsString('Resolved', $this->model->resolved_badge);

        // Test unresolved status
        $this->model->resolved = false;
        $this->assertStringContainsString('badge-secondary', $this->model->resolved_badge);
        $this->assertStringContainsString('Active', $this->model->resolved_badge);
    }
}
