<?php

namespace Database\Factories;

use App\Models\MysqlOptimizationLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MysqlOptimizationLog>
 */
class MysqlOptimizationLogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = MysqlOptimizationLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $settings = [
            'innodb_buffer_pool_size',
            'query_cache_size',
            'max_connections',
            'innodb_log_file_size',
            'key_buffer_size',
            'sort_buffer_size',
            'read_buffer_size',
            'table_open_cache'
        ];

        $priorities = ['high', 'medium', 'low'];

        return [
            'setting_name' => $this->faker->randomElement($settings),
            'current_value' => $this->faker->randomElement(['128M', '256M', '512M', '1G', '2G', '100', '200', '300']),
            'recommended_value' => $this->faker->randomElement(['256M', '512M', '1G', '2G', '4G', '150', '300', '500']),
            'description' => $this->faker->sentence(),
            'priority' => $this->faker->randomElement($priorities),
            'applied' => $this->faker->boolean(30) // 30% chance of being applied
        ];
    }

    /**
     * Indicate that the optimization is high priority.
     */
    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'high'
        ]);
    }

    /**
     * Indicate that the optimization is medium priority.
     */
    public function mediumPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'medium'
        ]);
    }

    /**
     * Indicate that the optimization is low priority.
     */
    public function lowPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'low'
        ]);
    }

    /**
     * Indicate that the optimization has been applied.
     */
    public function applied(): static
    {
        return $this->state(fn (array $attributes) => [
            'applied' => true
        ]);
    }

    /**
     * Indicate that the optimization has not been applied.
     */
    public function unapplied(): static
    {
        return $this->state(fn (array $attributes) => [
            'applied' => false
        ]);
    }

    /**
     * Create optimization for buffer pool size.
     */
    public function bufferPoolSize(): static
    {
        return $this->state(fn (array $attributes) => [
            'setting_name' => 'innodb_buffer_pool_size',
            'current_value' => '128M',
            'recommended_value' => '1G',
            'description' => 'InnoDB buffer pool size should be increased for better performance',
            'priority' => 'high'
        ]);
    }

    /**
     * Create optimization for max connections.
     */
    public function maxConnections(): static
    {
        return $this->state(fn (array $attributes) => [
            'setting_name' => 'max_connections',
            'current_value' => '151',
            'recommended_value' => '300',
            'description' => 'Maximum connections should be increased to handle more concurrent users',
            'priority' => 'medium'
        ]);
    }
}
