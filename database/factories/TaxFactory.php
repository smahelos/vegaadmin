<?php

namespace Database\Factories;

use App\Models\Tax;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tax>
 */
class TaxFactory extends Factory
{
    protected $model = Tax::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(2, true);
        
        return [
            'name' => $name,
            'rate' => fake()->randomFloat(2, 0, 30),
            'slug' => \Illuminate\Support\Str::slug($name),
        ];
    }

    /**
     * Create a tax with zero rate.
     */
    public function zero(): static
    {
        return $this->state(fn (array $attributes) => [
            'rate' => 0.00,
        ]);
    }

    /**
     * Create a tax with standard VAT rate.
     */
    public function standardVat(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Standard VAT',
            'rate' => 21.00,
            'slug' => 'standard-vat',
        ]);
    }

    /**
     * Create a tax with reduced VAT rate.
     */
    public function reducedVat(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Reduced VAT',
            'rate' => 15.00,
            'slug' => 'reduced-vat',
        ]);
    }
}
