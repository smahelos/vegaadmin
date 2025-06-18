<?php

namespace Tests\Feature\Models;

use App\Models\DatabaseHealthMetric;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Feature tests for DatabaseHealthMetric Model
 * 
 * Tests database operations, business logic, and model behavior requiring database interactions
 * Tests health metric creation, scopes, accessors, and data integrity
 */
class DatabaseHealthMetricFeatureTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    #[Test]
    public function can_create_database_health_metric_with_factory(): void
    {
        $metric = DatabaseHealthMetric::factory()->create();

        $this->assertDatabaseHas('database_health_metrics', [
            'id' => $metric->id,
            'metric_name' => $metric->metric_name,
            'status' => $metric->status,
        ]);
    }

    #[Test]
    public function fillable_attributes_can_be_mass_assigned(): void
    {
        $data = [
            'metric_name' => 'database_size',
            'metric_value' => 123.45,
            'metric_unit' => 'GB',
            'status' => 'warning',
            'recommendation' => 'Consider archive old data',
            'measured_at' => '2025-06-18 10:00:00',
        ];

        $metric = DatabaseHealthMetric::create($data);

        $this->assertDatabaseHas('database_health_metrics', $data);
        $this->assertEquals($data['metric_name'], $metric->metric_name);
        $this->assertEquals($data['status'], $metric->status);
    }

    #[Test]
    public function casts_work_correctly(): void
    {
        $metric = DatabaseHealthMetric::factory()->create([
            'metric_value' => '123.4567',
            'measured_at' => '2025-06-18 15:30:45',
        ]);

        $this->assertIsString($metric->metric_value); // Decimal cast returns string
        $this->assertEquals('123.4567', $metric->metric_value);
        $this->assertInstanceOf(\Carbon\Carbon::class, $metric->measured_at);
        $this->assertEquals('2025-06-18 15:30:45', $metric->measured_at->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function table_name_is_correct(): void
    {
        $metric = DatabaseHealthMetric::factory()->create();
        
        $this->assertEquals('database_health_metrics', $metric->getTable());
    }

    #[Test]
    public function get_status_badge_attribute_works(): void
    {
        $goodMetric = DatabaseHealthMetric::factory()->good()->create();
        $warningMetric = DatabaseHealthMetric::factory()->warning()->create();
        $criticalMetric = DatabaseHealthMetric::factory()->critical()->create();

        $this->assertStringContainsString('badge-success', $goodMetric->getStatusBadgeAttribute());
        $this->assertStringContainsString('Good', $goodMetric->getStatusBadgeAttribute());

        $this->assertStringContainsString('badge-warning', $warningMetric->getStatusBadgeAttribute());
        $this->assertStringContainsString('Warning', $warningMetric->getStatusBadgeAttribute());

        $this->assertStringContainsString('badge-danger', $criticalMetric->getStatusBadgeAttribute());
        $this->assertStringContainsString('Critical', $criticalMetric->getStatusBadgeAttribute());

        // Test unknown status by creating a metric and manually setting invalid status
        $unknownMetric = new DatabaseHealthMetric();
        $unknownMetric->status = 'unknown'; // This won't be saved, just for testing accessor
        $this->assertStringContainsString('badge-light', $unknownMetric->getStatusBadgeAttribute());
        $this->assertStringContainsString('Unknown', $unknownMetric->getStatusBadgeAttribute());
    }

    #[Test]
    public function get_formatted_value_attribute_works(): void
    {
        $metricWithUnit = DatabaseHealthMetric::factory()->create([
            'metric_value' => '50.25',
            'metric_unit' => 'GB',
        ]);

        $metricWithoutUnit = DatabaseHealthMetric::factory()->create([
            'metric_value' => '75.5',
            'metric_unit' => null,
        ]);

        $this->assertEquals('50.2500 GB', $metricWithUnit->getFormattedValueAttribute());
        $this->assertEquals('75.5000', $metricWithoutUnit->getFormattedValueAttribute());
    }

    #[Test]
    public function scope_recent_works(): void
    {
        // Create old metrics (older than 24 hours)
        DatabaseHealthMetric::factory()->create([
            'measured_at' => now()->subDays(2),
        ]);

        DatabaseHealthMetric::factory()->create([
            'measured_at' => now()->subHours(30),
        ]);

        // Create recent metrics (within 24 hours)
        $recentMetric1 = DatabaseHealthMetric::factory()->create([
            'measured_at' => now()->subHours(12),
        ]);

        $recentMetric2 = DatabaseHealthMetric::factory()->create([
            'measured_at' => now()->subHours(6),
        ]);

        $recentMetrics = DatabaseHealthMetric::recent()->get();

        $this->assertCount(2, $recentMetrics);
        $this->assertTrue($recentMetrics->contains($recentMetric1));
        $this->assertTrue($recentMetrics->contains($recentMetric2));
    }

    #[Test]
    public function scope_status_works(): void
    {
        DatabaseHealthMetric::factory()->good()->create();
        DatabaseHealthMetric::factory()->good()->create();
        DatabaseHealthMetric::factory()->warning()->create();
        DatabaseHealthMetric::factory()->critical()->create();

        $goodMetrics = DatabaseHealthMetric::status('good')->get();
        $warningMetrics = DatabaseHealthMetric::status('warning')->get();
        $criticalMetrics = DatabaseHealthMetric::status('critical')->get();

        $this->assertCount(2, $goodMetrics);
        $this->assertCount(1, $warningMetrics);
        $this->assertCount(1, $criticalMetrics);

        foreach ($goodMetrics as $metric) {
            $this->assertEquals('good', $metric->status);
        }
    }

    #[Test]
    public function scope_metric_type_works(): void
    {
        DatabaseHealthMetric::factory()->databaseSize()->create();
        DatabaseHealthMetric::factory()->databaseSize()->create();
        DatabaseHealthMetric::factory()->queryPerformance()->create();
        DatabaseHealthMetric::factory()->connectionCount()->create();

        $databaseSizeMetrics = DatabaseHealthMetric::metricType('database_size')->get();
        $queryPerformanceMetrics = DatabaseHealthMetric::metricType('query_performance')->get();
        $connectionCountMetrics = DatabaseHealthMetric::metricType('connection_count')->get();

        $this->assertCount(2, $databaseSizeMetrics);
        $this->assertCount(1, $queryPerformanceMetrics);
        $this->assertCount(1, $connectionCountMetrics);

        foreach ($databaseSizeMetrics as $metric) {
            $this->assertEquals('database_size', $metric->metric_name);
        }
    }

    #[Test]
    public function factory_states_work_correctly(): void
    {
        $goodMetric = DatabaseHealthMetric::factory()->good()->create();
        $warningMetric = DatabaseHealthMetric::factory()->warning()->create();
        $criticalMetric = DatabaseHealthMetric::factory()->critical()->create();

        $this->assertEquals('good', $goodMetric->status);
        $this->assertNull($goodMetric->recommendation);

        $this->assertEquals('warning', $warningMetric->status);
        $this->assertNotNull($warningMetric->recommendation);

        $this->assertEquals('critical', $criticalMetric->status);
        $this->assertNotNull($criticalMetric->recommendation);
        $this->assertStringContainsString('Immediate action required', $criticalMetric->recommendation);
    }

    #[Test]
    public function factory_metric_specific_states_work(): void
    {
        $databaseSizeMetric = DatabaseHealthMetric::factory()->databaseSize()->create();
        $queryPerformanceMetric = DatabaseHealthMetric::factory()->queryPerformance()->create();
        $connectionCountMetric = DatabaseHealthMetric::factory()->connectionCount()->create();

        $this->assertEquals('database_size', $databaseSizeMetric->metric_name);
        $this->assertEquals('GB', $databaseSizeMetric->metric_unit);

        $this->assertEquals('query_performance', $queryPerformanceMetric->metric_name);
        $this->assertEquals('ms', $queryPerformanceMetric->metric_unit);

        $this->assertEquals('connection_count', $connectionCountMetric->metric_name);
        $this->assertEquals('count', $connectionCountMetric->metric_unit);
    }

    #[Test]
    public function factory_recent_state_works(): void
    {
        $recentMetric = DatabaseHealthMetric::factory()->recent()->create();

        $this->assertTrue($recentMetric->measured_at->isAfter(now()->subDay()));
        $this->assertTrue($recentMetric->measured_at->isBefore(now()->addMinute()));
    }

    #[Test]
    public function factory_with_value_state_works(): void
    {
        $specificValueMetric = DatabaseHealthMetric::factory()->withValue(99.99)->create();

        $this->assertEquals('99.9900', $specificValueMetric->metric_value);
    }

    #[Test]
    public function can_update_database_health_metric(): void
    {
        $metric = DatabaseHealthMetric::factory()->create();
        
        $newData = [
            'status' => 'critical',
            'recommendation' => 'Urgent action needed',
            'metric_value' => 999.99,
        ];
        
        $metric->update($newData);
        
        $this->assertDatabaseHas('database_health_metrics', array_merge(
            ['id' => $metric->id],
            $newData
        ));
    }

    #[Test]
    public function can_delete_database_health_metric(): void
    {
        $metric = DatabaseHealthMetric::factory()->create();
        $metricId = $metric->id;
        
        $metric->delete();
        
        $this->assertDatabaseMissing('database_health_metrics', ['id' => $metricId]);
    }

    #[Test]
    public function can_create_metric_without_optional_fields(): void
    {
        $data = [
            'metric_name' => 'simple_test',
            'metric_value' => 50.0,
            'status' => 'good',
            'measured_at' => now(),
        ];

        $metric = DatabaseHealthMetric::create($data);

        $this->assertDatabaseHas('database_health_metrics', [
            'metric_name' => $data['metric_name'],
            'status' => $data['status'],
        ]);
        $this->assertNull($metric->metric_unit);
        $this->assertNull($metric->recommendation);
    }

    #[Test]
    public function chained_scopes_work_correctly(): void
    {
        // Create various metrics
        DatabaseHealthMetric::factory()->recent()->good()->databaseSize()->create();
        DatabaseHealthMetric::factory()->recent()->warning()->databaseSize()->create();
        DatabaseHealthMetric::factory()->create([
            'measured_at' => now()->subDays(2),
            'status' => 'good',
            'metric_name' => 'database_size',
        ]);

        // Test chained scopes
        $recentGoodDatabaseMetrics = DatabaseHealthMetric::recent()
            ->status('good')
            ->metricType('database_size')
            ->get();

        $this->assertCount(1, $recentGoodDatabaseMetrics);
        $metric = $recentGoodDatabaseMetrics->first();
        $this->assertEquals('good', $metric->status);
        $this->assertEquals('database_size', $metric->metric_name);
        $this->assertTrue($metric->measured_at->isAfter(now()->subDay()));
    }
}
