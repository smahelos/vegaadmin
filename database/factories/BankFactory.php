<?php

namespace Database\Factories;

use App\Models\Bank;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Bank>
 */
class BankFactory extends Factory
{
    protected $model = Bank::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company() . ' Bank',
            'code' => $this->faker->unique()->regexify('[0-9]{4}'),
            'swift' => $this->faker->optional()->regexify('[A-Z]{4}CZ[0-9A-Z]{4}'),
            'country' => $this->faker->randomElement(['CZ', 'SK', 'DE', 'AT']),
            'active' => $this->faker->boolean(85), // 85% chance of being active
            'description' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the bank is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => true,
        ]);
    }

    /**
     * Indicate that the bank is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }

    /**
     * Set a specific country for the bank.
     */
    public function country(string $country): static
    {
        return $this->state(fn (array $attributes) => [
            'country' => $country,
        ]);
    }

    /**
     * Create a Czech bank.
     */
    public function czech(): static
    {
        return $this->state(fn (array $attributes) => [
            'country' => 'CZ',
            'swift' => $this->faker->regexify('[A-Z]{4}CZ[0-9A-Z]{4}'),
        ]);
    }
}
