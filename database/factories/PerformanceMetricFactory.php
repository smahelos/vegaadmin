<?php

namespace Database\Factories;

use App\Models\PerformanceMetric;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PerformanceMetric>
 */
class PerformanceMetricFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PerformanceMetric::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $metricTypes = ['query_time', 'table_size', 'index_usage', 'connection_count', 'slow_queries'];
        $queryTypes = ['SELECT', 'INSERT', 'UPDATE', 'DELETE', null];
        $tables = ['users', 'invoices', 'clients', 'products', 'expenses', null];
        $units = ['ms', 'MB', 'GB', 'count', '%'];

        return [
            'metric_type' => $this->faker->randomElement($metricTypes),
            'table_name' => $this->faker->randomElement($tables),
            'query_type' => $this->faker->randomElement($queryTypes),
            'metric_value' => $this->faker->randomFloat(4, 0, 1000),
            'metric_unit' => $this->faker->randomElement($units),
            'metadata' => [
                'server_id' => $this->faker->numberBetween(1, 5),
                'environment' => $this->faker->randomElement(['production', 'staging', 'development']),
                'query_hash' => $this->faker->md5()
            ],
            'measured_at' => $this->faker->dateTimeBetween('-1 month', 'now')
        ];
    }

    /**
     * Indicate that the metric is for query time.
     */
    public function queryTime(): static
    {
        return $this->state(fn (array $attributes) => [
            'metric_type' => 'query_time',
            'metric_unit' => 'ms',
            'metric_value' => $this->faker->randomFloat(2, 0.1, 5000)
        ]);
    }

    /**
     * Indicate that the metric is for table size.
     */
    public function tableSize(): static
    {
        return $this->state(fn (array $attributes) => [
            'metric_type' => 'table_size',
            'metric_unit' => 'MB',
            'metric_value' => $this->faker->randomFloat(2, 1, 10000)
        ]);
    }

    /**
     * Indicate that the metric is for index usage.
     */
    public function indexUsage(): static
    {
        return $this->state(fn (array $attributes) => [
            'metric_type' => 'index_usage',
            'metric_unit' => '%',
            'metric_value' => $this->faker->randomFloat(2, 0, 100)
        ]);
    }

    /**
     * Indicate that the metric is for slow queries.
     */
    public function slowQueries(): static
    {
        return $this->state(fn (array $attributes) => [
            'metric_type' => 'slow_queries',
            'metric_unit' => 'count',
            'metric_value' => $this->faker->numberBetween(0, 100)
        ]);
    }

    /**
     * Indicate that the metric is for connection count.
     */
    public function connectionCount(): static
    {
        return $this->state(fn (array $attributes) => [
            'metric_type' => 'connection_count',
            'metric_unit' => 'count',
            'metric_value' => $this->faker->numberBetween(1, 300),
            'table_name' => null,
            'query_type' => null
        ]);
    }

    /**
     * Indicate that the metric is for a specific table.
     */
    public function forTable(string $tableName): static
    {
        return $this->state(fn (array $attributes) => [
            'table_name' => $tableName
        ]);
    }

    /**
     * Indicate that the metric is for a specific query type.
     */
    public function forQueryType(string $queryType): static
    {
        return $this->state(fn (array $attributes) => [
            'query_type' => $queryType
        ]);
    }

    /**
     * Indicate that the metric was measured recently.
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'measured_at' => $this->faker->dateTimeBetween('-7 days', 'now')
        ]);
    }

    /**
     * Indicate that the metric was measured long ago.
     */
    public function old(): static
    {
        return $this->state(fn (array $attributes) => [
            'measured_at' => $this->faker->dateTimeBetween('-6 months', '-2 months')
        ]);
    }
}
