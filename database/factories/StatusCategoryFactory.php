<?php

namespace Database\Factories;

use App\Models\StatusCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<StatusCategory>
 */
class StatusCategoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = StatusCategory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $faker = fake();
        $unique = $faker->unique();
        $name = $unique->words(2, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $faker->sentence(),
        ];
    }
}
