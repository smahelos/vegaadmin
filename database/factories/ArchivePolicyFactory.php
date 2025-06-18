<?php

namespace Database\Factories;

use App\Models\ArchivePolicy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ArchivePolicy>
 */
class ArchivePolicyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ArchivePolicy::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'table_name' => 'test_table_' . fake()->unique()->randomNumber(6),
            'retention_months' => fake()->numberBetween(12, 84), // 1-7 years
            'date_column' => fake()->randomElement([
                'created_at',
                'updated_at',
                'deleted_at',
                'archived_at'
            ]),
            'enabled' => fake()->boolean(80), // 80% chance of being enabled
            'last_archived_at' => fake()->optional(0.6)->dateTimeBetween('-1 year', 'now'),
            'records_archived' => fake()->numberBetween(0, 10000),
        ];
    }

    /**
     * Indicate that the archive policy is enabled.
     */
    public function enabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'enabled' => true,
        ]);
    }

    /**
     * Indicate that the archive policy is disabled.
     */
    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'enabled' => false,
        ]);
    }

    /**
     * Indicate that the archive policy has never been run.
     */
    public function neverRun(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_archived_at' => null,
            'records_archived' => 0,
        ]);
    }

    /**
     * Indicate that the archive policy was recently run.
     */
    public function recentlyRun(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_archived_at' => fake()->dateTimeBetween('-1 week', 'now'),
            'records_archived' => fake()->numberBetween(100, 5000),
        ]);
    }
}
