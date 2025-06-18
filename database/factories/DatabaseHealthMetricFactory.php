<?php

namespace Database\Factories;

use App\Models\DatabaseHealthMetric;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DatabaseHealthMetric>
 */
class DatabaseHealthMetricFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = DatabaseHealthMetric::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $metricNames = [
            'database_size',
            'query_performance',
            'connection_count',
            'table_fragmentation',
            'index_efficiency',
            'slow_query_count',
            'memory_usage',
            'disk_usage',
            'backup_status',
            'replication_lag'
        ];

        $units = [
            'MB',
            'GB',
            'ms',
            '%',
            'count',
            'seconds',
            null
        ];

        $statuses = ['good', 'warning', 'critical'];

        return [
            'metric_name' => $this->faker->randomElement($metricNames),
            'metric_value' => $this->faker->randomFloat(4, 0, 1000),
            'metric_unit' => $this->faker->randomElement($units),
            'status' => $this->faker->randomElement($statuses),
            'recommendation' => $this->faker->optional()->sentence(),
            'measured_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
        ];
    }

    /**
     * Create a metric with good status.
     */
    public function good(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'good',
            'recommendation' => null,
        ]);
    }

    /**
     * Create a metric with warning status.
     */
    public function warning(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'warning',
            'recommendation' => $this->faker->sentence(),
        ]);
    }

    /**
     * Create a metric with critical status.
     */
    public function critical(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'critical',
            'recommendation' => 'Immediate action required: ' . $this->faker->sentence(),
        ]);
    }

    /**
     * Create a metric for database size.
     */
    public function databaseSize(): static
    {
        return $this->state(fn (array $attributes) => [
            'metric_name' => 'database_size',
            'metric_unit' => 'GB',
            'metric_value' => $this->faker->randomFloat(2, 0.1, 50),
        ]);
    }

    /**
     * Create a metric for query performance.
     */
    public function queryPerformance(): static
    {
        return $this->state(fn (array $attributes) => [
            'metric_name' => 'query_performance',
            'metric_unit' => 'ms',
            'metric_value' => $this->faker->randomFloat(2, 1, 5000),
        ]);
    }

    /**
     * Create a metric for connection count.
     */
    public function connectionCount(): static
    {
        return $this->state(fn (array $attributes) => [
            'metric_name' => 'connection_count',
            'metric_unit' => 'count',
            'metric_value' => $this->faker->numberBetween(1, 100),
        ]);
    }

    /**
     * Create a recent metric (within last 24 hours).
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'measured_at' => $this->faker->dateTimeBetween('-24 hours', 'now'),
        ]);
    }

    /**
     * Create a metric with specific value.
     */
    public function withValue(float $value): static
    {
        return $this->state(fn (array $attributes) => [
            'metric_value' => $value,
        ]);
    }
}
