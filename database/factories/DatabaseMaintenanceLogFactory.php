<?php

namespace Database\Factories;

use App\Models\DatabaseMaintenanceLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DatabaseMaintenanceLog>
 */
class DatabaseMaintenanceLogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = DatabaseMaintenanceLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $taskTypes = ['optimize', 'analyze', 'check', 'repair', 'cleanup'];
        $statuses = ['pending', 'running', 'completed', 'failed'];
        
        $startedAt = $this->faker->dateTimeBetween('-1 month', 'now');
        $completedAt = $this->faker->optional(0.8)->dateTimeBetween($startedAt, 'now');

        return [
            'task_type' => $this->faker->randomElement($taskTypes),
            'table_name' => $this->faker->randomElement(['users', 'invoices', 'clients', 'products', 'expenses']),
            'status' => $this->faker->randomElement($statuses),
            'description' => $this->faker->sentence(),
            'results' => [
                'rows_processed' => $this->faker->numberBetween(0, 10000),
                'execution_time' => $this->faker->randomFloat(2, 0.1, 60.0),
                'memory_usage' => $this->faker->numberBetween(1, 100) . 'MB'
            ],
            'started_at' => $startedAt,
            'completed_at' => $completedAt
        ];
    }

    /**
     * Indicate that the maintenance task is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'started_at' => null,
            'completed_at' => null
        ]);
    }

    /**
     * Indicate that the maintenance task is running.
     */
    public function running(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'running',
            'started_at' => $this->faker->dateTimeBetween('-1 hour', 'now'),
            'completed_at' => null
        ]);
    }

    /**
     * Indicate that the maintenance task is completed.
     */
    public function completed(): static
    {
        $startedAt = $this->faker->dateTimeBetween('-1 day', '-1 hour');
        
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'started_at' => $startedAt,
            'completed_at' => $this->faker->dateTimeBetween($startedAt, 'now')
        ]);
    }

    /**
     * Indicate that the maintenance task failed.
     */
    public function failed(): static
    {
        $startedAt = $this->faker->dateTimeBetween('-1 day', '-1 hour');
        
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'started_at' => $startedAt,
            'completed_at' => $this->faker->dateTimeBetween($startedAt, 'now'),
            'results' => [
                'error_message' => $this->faker->sentence(),
                'error_code' => $this->faker->numberBetween(1000, 9999)
            ]
        ]);
    }
}
