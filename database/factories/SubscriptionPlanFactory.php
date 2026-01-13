<?php

namespace Database\Factories;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SubscriptionPlan>
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
        $plans = ['Basic', 'Professional', 'Enterprise'];
        $periods = ['monthly', 'yearly'];
        
        return [
            'name' => $this->faker->randomElement($plans) . ' Plan',
            'description' => $this->faker->sentence(10),
            'price' => $this->faker->randomFloat(2, 9.99, 199.99),
            'currency' => 'EUR',
            'billing_period' => $this->faker->randomElement($periods),
            'billing_interval' => 1,
            'is_active' => true,
            'trial_days' => $this->faker->numberBetween(0, 30),
        ];
    }

    /**
     * Indicate that the plan is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
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

    /**
     * Indicate that the plan is monthly.
     */
    public function monthly(): static
    {
        return $this->state(fn (array $attributes) => [
            'billing_period' => 'monthly',
            'billing_interval' => 1,
        ]);
    }

    /**
     * Indicate that the plan is yearly.
     */
    public function yearly(): static
    {
        return $this->state(fn (array $attributes) => [
            'billing_period' => 'yearly',
            'billing_interval' => 1,
        ]);
    }

    /**
     * Indicate that the plan has features attached.
     */
    public function withFeatures(): static
    {
        return $this->afterCreating(function (SubscriptionPlan $plan) {
            // Create 2-4 random features and attach them to the plan
            $featureCount = $this->faker->numberBetween(2, 4);
            $features = \App\Models\SubscriptionPlanFeature::factory($featureCount)->create();
            $plan->features()->attach($features);
        });
    }
}
