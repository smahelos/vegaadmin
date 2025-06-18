<?php

namespace Tests\Feature\Models;

use App\Models\PerformanceMetric;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PerformanceMetricFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function can_create_performance_metric(): void
    {
        $data = [
            'metric_type' => 'query_time',
            'table_name' => 'users',
            'query_type' => 'SELECT',
            'metric_value' => 123.45,
            'metric_unit' => 'ms',
            'metadata' => ['server_id' => 1],
            'measured_at' => now()
        ];

        $metric = PerformanceMetric::create($data);

        $this->assertInstanceOf(PerformanceMetric::class, $metric);
        $this->assertDatabaseHas('performance_metrics', [
            'metric_type' => 'query_time',
            'table_name' => 'users',
            'query_type' => 'SELECT',
            'metric_unit' => 'ms'
        ]);
    }

    #[Test]
    public function can_use_factory_to_create_performance_metric(): void
    {
        $metric = PerformanceMetric::factory()->create();

        $this->assertInstanceOf(PerformanceMetric::class, $metric);
        $this->assertDatabaseHas('performance_metrics', [
            'id' => $metric->id
        ]);
    }

    #[Test]
    public function factory_query_time_state_creates_query_time_metric(): void
    {
        $metric = PerformanceMetric::factory()->queryTime()->create();

        $this->assertEquals('query_time', $metric->metric_type);
        $this->assertEquals('ms', $metric->metric_unit);
        $this->assertGreaterThanOrEqual(0.1, $metric->metric_value);
    }

    #[Test]
    public function factory_table_size_state_creates_table_size_metric(): void
    {
        $metric = PerformanceMetric::factory()->tableSize()->create();

        $this->assertEquals('table_size', $metric->metric_type);
        $this->assertEquals('MB', $metric->metric_unit);
        $this->assertGreaterThanOrEqual(1, $metric->metric_value);
    }

    #[Test]
    public function factory_index_usage_state_creates_index_usage_metric(): void
    {
        $metric = PerformanceMetric::factory()->indexUsage()->create();

        $this->assertEquals('index_usage', $metric->metric_type);
        $this->assertEquals('%', $metric->metric_unit);
        $this->assertGreaterThanOrEqual(0, $metric->metric_value);
        $this->assertLessThanOrEqual(100, $metric->metric_value);
    }

    #[Test]
    public function factory_slow_queries_state_creates_slow_queries_metric(): void
    {
        $metric = PerformanceMetric::factory()->slowQueries()->create();

        $this->assertEquals('slow_queries', $metric->metric_type);
        $this->assertEquals('count', $metric->metric_unit);
        $this->assertGreaterThanOrEqual(0, $metric->metric_value);
    }

    #[Test]
    public function factory_connection_count_state_creates_connection_count_metric(): void
    {
        $metric = PerformanceMetric::factory()->connectionCount()->create();

        $this->assertEquals('connection_count', $metric->metric_type);
        $this->assertEquals('count', $metric->metric_unit);
        $this->assertNull($metric->table_name);
        $this->assertNull($metric->query_type);
    }

    #[Test]
    public function factory_for_table_state_sets_table_name(): void
    {
        $metric = PerformanceMetric::factory()->forTable('invoices')->create();

        $this->assertEquals('invoices', $metric->table_name);
    }

    #[Test]
    public function factory_for_query_type_state_sets_query_type(): void
    {
        $metric = PerformanceMetric::factory()->forQueryType('UPDATE')->create();

        $this->assertEquals('UPDATE', $metric->query_type);
    }

    #[Test]
    public function factory_recent_state_creates_recent_metric(): void
    {
        $metric = PerformanceMetric::factory()->recent()->create();

        $this->assertGreaterThanOrEqual(now()->subDays(7), $metric->measured_at);
        $this->assertLessThanOrEqual(now(), $metric->measured_at);
    }

    #[Test]
    public function factory_old_state_creates_old_metric(): void
    {
        $metric = PerformanceMetric::factory()->old()->create();

        $this->assertLessThanOrEqual(now()->subMonths(2), $metric->measured_at);
        $this->assertGreaterThanOrEqual(now()->subMonths(6), $metric->measured_at);
    }

    #[Test]
    public function metadata_attribute_is_cast_to_array(): void
    {
        $metric = PerformanceMetric::factory()->create([
            'metadata' => ['test' => 'value', 'number' => 123]
        ]);

        $this->assertIsArray($metric->metadata);
        $this->assertEquals(['test' => 'value', 'number' => 123], $metric->metadata);
    }

    #[Test]
    public function measured_at_attribute_is_cast_to_datetime(): void
    {
        $metric = PerformanceMetric::factory()->create([
            'measured_at' => '2023-01-01 10:00:00'
        ]);

        $this->assertInstanceOf(Carbon::class, $metric->measured_at);
        $this->assertEquals('2023-01-01 10:00:00', $metric->measured_at->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function metric_value_attribute_is_cast_to_decimal(): void
    {
        $metric = PerformanceMetric::factory()->create([
            'metric_value' => 123.456789
        ]);

        // Check that the value is rounded to 4 decimal places
        $this->assertEquals('123.4568', (string) $metric->metric_value);
    }

    #[Test]
    public function formatted_value_attribute_works_with_database(): void
    {
        $metric = PerformanceMetric::factory()->create([
            'metric_value' => 123.45,
            'metric_unit' => 'ms'
        ]);

        $this->assertEquals('123.45 ms', $metric->formatted_value);
    }

    #[Test]
    public function metric_type_formatted_attribute_works_with_database(): void
    {
        $metric = PerformanceMetric::factory()->create([
            'metric_type' => 'query_execution_time'
        ]);

        $this->assertEquals('Query execution time', $metric->metric_type_formatted);
    }

    #[Test]
    public function scope_of_type_filters_correctly(): void
    {
        PerformanceMetric::factory()->queryTime()->createMany(2);
        PerformanceMetric::factory()->tableSize()->create();
        PerformanceMetric::factory()->indexUsage()->create();

        $queryTimeMetrics = PerformanceMetric::ofType('query_time')->get();

        $this->assertCount(2, $queryTimeMetrics);
        $this->assertTrue($queryTimeMetrics->every(fn($metric) => $metric->metric_type === 'query_time'));
    }

    #[Test]
    public function scope_for_table_filters_correctly(): void
    {
        PerformanceMetric::factory()->forTable('users')->createMany(2);
        PerformanceMetric::factory()->forTable('invoices')->create();
        PerformanceMetric::factory()->create(['table_name' => null]);

        $userMetrics = PerformanceMetric::forTable('users')->get();

        $this->assertCount(2, $userMetrics);
        $this->assertTrue($userMetrics->every(fn($metric) => $metric->table_name === 'users'));
    }

    #[Test]
    public function scope_recent_filters_correctly(): void
    {
        PerformanceMetric::factory()->recent()->createMany(2);
        PerformanceMetric::factory()->old()->createMany(3);

        $recentMetrics = PerformanceMetric::recent()->get();

        $this->assertCount(2, $recentMetrics);
        $this->assertTrue($recentMetrics->every(fn($metric) => $metric->measured_at >= now()->subDays(30)));
    }

    #[Test]
    public function scope_recent_accepts_custom_days(): void
    {
        PerformanceMetric::factory()->create(['measured_at' => now()->subDays(5)]);
        PerformanceMetric::factory()->create(['measured_at' => now()->subDays(15)]);
        PerformanceMetric::factory()->create(['measured_at' => now()->subDays(35)]);

        $recentMetrics = PerformanceMetric::recent(10)->get();

        $this->assertCount(1, $recentMetrics);
    }

    #[Test]
    public function can_combine_scopes(): void
    {
        PerformanceMetric::factory()->queryTime()->forTable('users')->recent()->createMany(2);
        PerformanceMetric::factory()->queryTime()->forTable('invoices')->recent()->create();
        PerformanceMetric::factory()->tableSize()->forTable('users')->recent()->create();
        PerformanceMetric::factory()->queryTime()->forTable('users')->old()->create();

        $metrics = PerformanceMetric::ofType('query_time')->forTable('users')->recent()->get();

        $this->assertCount(2, $metrics);
        $this->assertTrue($metrics->every(fn($metric) => 
            $metric->metric_type === 'query_time' && 
            $metric->table_name === 'users' && 
            $metric->measured_at >= now()->subDays(30)
        ));
    }

    #[Test]
    public function can_query_metrics_by_query_type(): void
    {
        PerformanceMetric::factory()->forQueryType('SELECT')->createMany(2);
        PerformanceMetric::factory()->forQueryType('INSERT')->create();
        PerformanceMetric::factory()->create(['query_type' => null]);

        $selectMetrics = PerformanceMetric::where('query_type', 'SELECT')->get();
        $insertMetrics = PerformanceMetric::where('query_type', 'INSERT')->get();

        $this->assertCount(2, $selectMetrics);
        $this->assertCount(1, $insertMetrics);
    }

    #[Test]
    public function can_update_performance_metric(): void
    {
        $metric = PerformanceMetric::factory()->create();

        $metric->update([
            'metric_value' => 999.99,
            'metric_unit' => 'seconds'
        ]);

        $this->assertEquals(999.99, $metric->fresh()->metric_value);
        $this->assertEquals('seconds', $metric->fresh()->metric_unit);
    }

    #[Test]
    public function can_delete_performance_metric(): void
    {
        $metric = PerformanceMetric::factory()->create();
        $metricId = $metric->id;

        $metric->delete();

        $this->assertDatabaseMissing('performance_metrics', [
            'id' => $metricId
        ]);
    }
}
