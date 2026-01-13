<?php

namespace Database\Factories;

use App\Models\EntityLimit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EntityLimit>
 */
class EntityLimitFactory extends Factory
{
    protected $model = EntityLimit::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $entityTypes = ['invoice', 'client', 'supplier', 'product', 'expense'];
        $periodTypes = ['daily', 'weekly', 'monthly', 'yearly', 'lifetime'];
        $metricTypes = ['count', 'value', 'size'];

        return [
            'permission_name' => $this->faker->unique()->words(3, true),
            'entity_type' => $this->faker->randomElement($entityTypes),
            'limit_value' => $this->faker->numberBetween(1, 100),
            'default_period' => $this->faker->randomElement(['monthly']),
            'period_type' => $this->faker->randomElement($periodTypes),
            'metric_type' => $this->faker->randomElement($metricTypes),
            'description' => $this->faker->optional()->sentence(),
            'is_active' => $this->faker->boolean(85), // 85% chance of being active
        ];
    }

    /**
     * Indicate that the limit is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the limit is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a count-based limit.
     */
    public function countLimit(int $maxCount = 10): static
    {
        return $this->state(fn (array $attributes) => [
            'metric_type' => 'count',
            'limit_value' => $maxCount,
        ]);
    }

    /**
     * Create a value-based limit.
     */
    public function valueLimit(float $maxValue = 1000.00): static
    {
        return $this->state(fn (array $attributes) => [
            'metric_type' => 'value',
            'limit_value' => $maxValue,
        ]);
    }

    /**
     * Create a size-based limit.
     */
    public function sizeLimit(int $maxSize = 1048576): static // 1MB default
    {
        return $this->state(fn (array $attributes) => [
            'metric_type' => 'size',
            'limit_value' => $maxSize,
        ]);
    }

    /**
     * Create a limit for a specific entity type.
     */
    public function forEntity(string $entityType): static
    {
        return $this->state(fn (array $attributes) => [
            'entity_type' => $entityType,
            'permission_name' => "can_create_edit_{$entityType}",
        ]);
    }

    /**
     * Create a monthly limit.
     */
    public function monthly(): static
    {
        return $this->state(fn (array $attributes) => [
            'period_type' => 'monthly',
        ]);
    }

    /**
     * Create a daily limit.
     */
    public function daily(): static
    {
        return $this->state(fn (array $attributes) => [
            'period_type' => 'daily',
        ]);
    }

    /**
     * Create an unlimited limit (very high max values).
     */
    public function unlimited(): static
    {
        return $this->state(fn (array $attributes) => [
            'limit_value' => 999999,
        ]);
    }

    /**
     * Create a strict limit (low max values).
     */
    public function strict(): static
    {
        return $this->state(fn (array $attributes) => [
            'limit_value' => 1,
        ]);
    }
}
