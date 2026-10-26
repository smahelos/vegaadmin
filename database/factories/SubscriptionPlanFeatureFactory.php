<?php

namespace Database\Factories;

use App\Models\SubscriptionPlanFeature;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubscriptionPlanFeature>
 */
class SubscriptionPlanFeatureFactory extends Factory
{
    protected $model = SubscriptionPlanFeature::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->words(3, true);

        return [
            'name' => $name,
            'description' => $this->faker->optional()->paragraph(),
            'slug' => $this->faker->unique()->slug(),
            'is_active' => $this->faker->boolean(85),
            'sort_order' => $this->faker->numberBetween(0, 100),
        ];
    }
}
