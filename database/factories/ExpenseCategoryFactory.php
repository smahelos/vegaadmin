<?php

namespace Database\Factories;

use App\Models\ExpenseCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ExpenseCategory>
 */
class ExpenseCategoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = ExpenseCategory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->words(2, true);
        
        return [
            'name' => $name,
            'slug' => $this->faker->unique()->slug(),
            'description' => $this->faker->sentence(),
            'color' => $this->faker->hexColor(),
            'is_active' => $this->faker->boolean(85), // 85% chance of being active
        ];
    }

    /**
     * Indicate that the category is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the category is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a category with a specific name and color.
     */
    public function withNameAndColor(string $name, string $color = null): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name),
            'color' => $color ?? $this->faker->hexColor(),
        ]);
    }

    /**
     * Create a category with common expense types.
     */
    public function office(): static
    {
        return $this->withNameAndColor('Office Supplies', '#3498db');
    }

    /**
     * Create a travel expense category.
     */
    public function travel(): static
    {
        return $this->withNameAndColor('Travel Expenses', '#e74c3c');
    }

    /**
     * Create a marketing expense category.
     */
    public function marketing(): static
    {
        return $this->withNameAndColor('Marketing', '#f39c12');
    }
}
