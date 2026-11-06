<?php

namespace Tests\Feature\Models;

use App\Models\PerformanceMetric;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PerformanceMetricFeatureTest extends TestCase
{
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
            'measured_at' => now(),
        ];

        $metric = PerformanceMetric::create($data);

        $this->assertInstanceOf(PerformanceMetric::class, $metric);
        $this->assertDatabaseHas(
            'performance_metrics',
            [
                'metric_type' => 'query_time',
                'table_name' => 'users',
                'query_type' => 'SELECT',
                'metric_unit' => 'ms',
            ],
        );
    }

    #[Test]
    public function can_use_factory_to_create_performance_metric(): void
    {

        $performanceMetricFactory = PerformanceMetric::factory();
        $metric = $performanceMetricFactory->create();

        $this->assertInstanceOf(PerformanceMetric::class, $metric);
        $this->assertDatabaseHas('performance_metrics', [
            'id' => $metric->id,
        ]);
    }

    #[Test]
    public function factory_query_time_state_creates_query_time_metric(): void
    {

        $performanceMetricFactory = PerformanceMetric::factory();
        $queryTimeFactory = $performanceMetricFactory->queryTime();
        $metric = $queryTimeFactory->create();

        $this->assertEquals('query_time', $metric->metric_type);
        $this->assertEquals('ms', $metric->metric_unit);
        $this->assertGreaterThanOrEqual(0.1, $metric->metric_value);
    }

    #[Test]
    public function factory_table_size_state_creates_table_size_metric(): void
    {

        $performanceMetricFactory = PerformanceMetric::factory();
        $tableSizeFactory = $performanceMetricFactory->tableSize();
        $metric = $tableSizeFactory->create();

        $this->assertEquals('table_size', $metric->metric_type);
        $this->assertEquals('MB', $metric->metric_unit);
        $this->assertGreaterThanOrEqual(1, $metric->metric_value);
    }

    #[Test]
    public function factory_index_usage_state_creates_index_usage_metric(): void
    {

        $performanceMetricFactory = PerformanceMetric::factory();
        $indexUsageFactory = $performanceMetricFactory->indexUsage();
        $metric = $indexUsageFactory->create();

        $this->assertEquals('index_usage', $metric->metric_type);
        $this->assertEquals('%', $metric->metric_unit);
        $this->assertGreaterThanOrEqual(0, $metric->metric_value);
        $this->assertLessThanOrEqual(100, $metric->metric_value);
    }

    #[Test]
    public function factory_slow_queries_state_creates_slow_queries_metric(): void
    {

        $performanceMetricFactory = PerformanceMetric::factory();
        $slowQueriesFactory = $performanceMetricFactory->slowQueries();
        $metric = $slowQueriesFactory->create();

        $this->assertEquals('slow_queries', $metric->metric_type);
        $this->assertEquals('count', $metric->metric_unit);
        $this->assertGreaterThanOrEqual(0, $metric->metric_value);
    }

    #[Test]
    public function factory_connection_count_state_creates_connection_count_metric(): void
    {

        $performanceMetricFactory = PerformanceMetric::factory();
        $connectionCountFactory = $performanceMetricFactory->connectionCount();
        $metric = $connectionCountFactory->create();

        $this->assertEquals('connection_count', $metric->metric_type);
        $this->assertEquals('count', $metric->metric_unit);
        $this->assertNull($metric->table_name);
        $this->assertNull($metric->query_type);
    }

    #[Test]
    public function factory_for_table_state_sets_table_name(): void
    {

        $performanceMetricFactory = PerformanceMetric::factory();
        $tableFactory = $performanceMetricFactory->forTable('invoices');
        $metric = $tableFactory->create();

        $this->assertEquals('invoices', $metric->table_name);
    }

    #[Test]
    public function factory_for_query_type_state_sets_query_type(): void
    {

        $performanceMetricFactory = PerformanceMetric::factory();
        $queryTypeFactory = $performanceMetricFactory->forQueryType('UPDATE');
        $metric = $queryTypeFactory->create();

        $this->assertEquals('UPDATE', $metric->query_type);
    }

    #[Test]
    public function factory_recent_state_creates_recent_metric(): void
    {

        $performanceMetricFactory = PerformanceMetric::factory();
        $recentFactory = $performanceMetricFactory->recent();
        $metric = $recentFactory->create();

        $now = CarbonImmutable::now();
        $this->assertGreaterThanOrEqual(
            $now->subDays(7),
            $metric->measured_at,
        );
        $this->assertLessThanOrEqual(now(), $metric->measured_at);
    }

    #[Test]
    public function factory_old_state_creates_old_metric(): void
    {

        $performanceMetricFactory = PerformanceMetric::factory();
        $oldFactory = $performanceMetricFactory->old();
        $metric = $oldFactory->create();

        $now = CarbonImmutable::now();
        $this->assertLessThanOrEqual(
            $now->subMonths(2),
            $metric->measured_at,
        );
        $this->assertGreaterThanOrEqual(
            $now->subMonths(6),
            $metric->measured_at,
        );
    }

    #[Test]
    public function metadata_attribute_is_cast_to_array(): void
    {

        $performanceMetricFactory = PerformanceMetric::factory();
        $metric = $performanceMetricFactory->create([
            'metadata' => ['test' => 'value', 'number' => 123],
        ]);

        $this->assertIsArray($metric->metadata);
        $this->assertEquals(['test' => 'value', 'number' => 123], $metric->metadata);
    }

    #[Test]
    public function measured_at_attribute_is_cast_to_datetime(): void
    {

        $performanceMetricFactory = PerformanceMetric::factory();
        $metric = $performanceMetricFactory->create([
            'measured_at' => '2023-01-01 10:00:00',
        ]);

        $this->assertInstanceOf(Carbon::class, $metric->measured_at);
        $this->assertEquals(
            '2023-01-01 10:00:00',
            $metric->measured_at->format('Y-m-d H:i:s'),
        );
    }

    #[Test]
    public function metric_value_attribute_is_cast_to_decimal(): void
    {

        $performanceMetricFactory = PerformanceMetric::factory();
        $metric = $performanceMetricFactory->create([
            'metric_value' => 123.456789,
        ]);

        // Check that the value is rounded to 4 decimal places
        $this->assertEquals('123.4568', (string) $metric->metric_value);
    }

    #[Test]
    public function can_query_metrics_by_query_type(): void
    {

        $performanceMetricFactory = PerformanceMetric::factory();
        $queryTypeFactory = $performanceMetricFactory->forQueryType('SELECT');
        $queryTypeFactory->createMany(2);
        $queryTypeFactory = $performanceMetricFactory->forQueryType('INSERT');
        $queryTypeFactory->create();
        $performanceMetricFactory->create(['query_type' => null]);

        $performanceMetricQuery = PerformanceMetric::where('query_type', 'SELECT');
        $selectMetrics = $performanceMetricQuery->get();
        $performanceMetricQuery = PerformanceMetric::where('query_type', 'INSERT');
        $insertMetrics = $performanceMetricQuery->get();

        $this->assertCount(2, $selectMetrics);
        $this->assertCount(1, $insertMetrics);
    }

    #[Test]
    public function can_update_performance_metric(): void
    {

        $performanceMetricFactory = PerformanceMetric::factory();
        $metric = $performanceMetricFactory->create();

        $metric->update([
            'metric_value' => 999.99,
            'metric_unit' => 'seconds',
        ]);

        $this->assertEquals(999.99, $metric->fresh()->metric_value);
        $this->assertEquals('seconds', $metric->fresh()->metric_unit);
    }

    #[Test]
    public function can_delete_performance_metric(): void
    {

        $performanceMetricFactory = PerformanceMetric::factory();
        $metric = $performanceMetricFactory->create();
        $metricId = $metric->id;

        $metric->delete();

        $this->assertDatabaseMissing('performance_metrics', [
            'id' => $metricId,
        ]);
    }
}
