<?php

namespace Database\Factories;

use App\Models\Status;
use App\Models\StatusCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Status>
 */
class StatusFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Status::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $rand = uniqid();
        $name = 'Status ' . substr($rand, -5);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'category_id' => StatusCategory::factory(),
            'color' => 'blue',
            'description' => 'Test status ' . $name,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the status should be active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the status should be inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a status without color.
     */
    public function withoutColor(): static
    {
        return $this->state(fn (array $attributes) => [
            'color' => null,
        ]);
    }

    /**
     * Create a status without description.
     */
    public function withoutDescription(): static
    {
        return $this->state(fn (array $attributes) => [
            'description' => null,
        ]);
    }
}
