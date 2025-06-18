<?php

namespace Database\Factories;

use App\Models\DatabaseHealthAlert;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DatabaseHealthAlert>
 */
class DatabaseHealthAlertFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = DatabaseHealthAlert::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'alert_type' => $this->faker->randomElement(['memory', 'disk', 'cpu', 'connections', 'slow_queries']),
            'severity' => $this->faker->randomElement(['info', 'warning', 'critical']),
            'message' => $this->faker->sentence(),
            'metric_data' => [
                'value' => $this->faker->randomFloat(2, 0, 100),
                'threshold' => $this->faker->randomFloat(2, 50, 100),
                'unit' => $this->faker->randomElement(['%', 'MB', 'GB', 'seconds']),
            ],
            'resolved' => $this->faker->boolean(),
            'resolved_at' => $this->faker->optional(0.7)->dateTimeBetween('-30 days'),
        ];
    }

    /**
     * State for critical alerts.
     */
    public function critical(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'severity' => 'critical',
                'resolved' => false,
                'resolved_at' => null,
                'metric_data' => [
                    'value' => $this->faker->randomFloat(2, 80, 100),
                    'threshold' => $this->faker->randomFloat(2, 50, 80),
                    'unit' => '%',
                ],
            ];
        });
    }

    /**
     * State for warning alerts.
     */
    public function warning(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'severity' => 'warning',
                'metric_data' => [
                    'value' => $this->faker->randomFloat(2, 60, 80),
                    'threshold' => $this->faker->randomFloat(2, 50, 70),
                    'unit' => '%',
                ],
            ];
        });
    }

    /**
     * State for info alerts.
     */
    public function info(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'severity' => 'info',
                'resolved' => $this->faker->boolean(80), // 80% chance of being resolved
                'metric_data' => [
                    'value' => $this->faker->randomFloat(2, 30, 60),
                    'threshold' => $this->faker->randomFloat(2, 40, 70),
                    'unit' => '%',
                ],
            ];
        });
    }

    /**
     * State for unresolved alerts.
     */
    public function unresolved(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'resolved' => false,
                'resolved_at' => null,
            ];
        });
    }

    /**
     * State for resolved alerts.
     */
    public function resolved(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'resolved' => true,
                'resolved_at' => $this->faker->dateTimeBetween('-30 days'),
            ];
        });
    }

    /**
     * State for memory alerts.
     */
    public function memory(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'alert_type' => 'memory',
                'message' => 'High memory usage detected',
                'metric_data' => [
                    'value' => $this->faker->randomFloat(2, 70, 95),
                    'threshold' => 80,
                    'unit' => '%',
                ],
            ];
        });
    }

    /**
     * State for disk alerts.
     */
    public function disk(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'alert_type' => 'disk',
                'message' => 'High disk usage detected',
                'metric_data' => [
                    'value' => $this->faker->randomFloat(2, 70, 95),
                    'threshold' => 85,
                    'unit' => '%',
                ],
            ];
        });
    }
}
