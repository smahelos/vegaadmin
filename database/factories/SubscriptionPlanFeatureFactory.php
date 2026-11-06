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
        $unique = $this->faker->unique();
        $name = $unique->words(3, true);
        $optional = $this->faker->optional();

        return [
            'name' => $name,
            'description' => $optional->paragraph(),
            'slug' => $unique->slug(),
            'is_active' => $this->faker->boolean(85),
            'sort_order' => $this->faker->numberBetween(0, 100),
        ];
    }
}
