<?php

namespace Database\Factories;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubscriptionPlan>
 */
class SubscriptionPlanFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = SubscriptionPlan::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $plans = [
            'Basic',
            'Professional',
            'Enterprise',
        ];
        $periods = ['monthly', 'yearly'];

        return [
            'name' => $this->faker->randomElement($plans) . ' Plan',
            'description' => $this->faker->sentence(10),
            'price' => $this->faker->randomFloat(
                2,
                9.99,
                199.99,
            ),
            'currency' => 'EUR',
            'billing_period' => $this->faker->randomElement($periods),
            'billing_interval' => 1,
            'is_active' => true,
            'trial_days' => $this->faker->numberBetween(0, 30),
        ];
    }

    /**
     * Indicate that the plan has no trial period.
     */
    public function withoutTrial(): static
    {
        return $this->state(fn (array $attributes) => [
            'trial_days' => 0,
        ]);
    }
}
