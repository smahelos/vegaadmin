<?php

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subscription>
 */
class SubscriptionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Subscription::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = $this->faker->randomElement(['active', 'expired', 'cancelled', 'pending']);
        $startDate = $this->faker->dateTimeBetween('-6 months', 'now');
        
        return [
            'user_id' => User::factory(),
            'subscription_plan_id' => SubscriptionPlan::factory(),
            'status' => $status,
            'starts_at' => $startDate,
            'ends_at' => $this->faker->dateTimeBetween($startDate, '+1 year'),
            'trial_ends_at' => $status === 'trial' ? $this->faker->dateTimeBetween('now', '+30 days') : null,
            'next_billing_at' => $this->faker->dateTimeBetween('now', '+2 months'),
            'amount' => $this->faker->randomFloat(2, 9.99, 199.99),
            'currency' => 'EUR',
            'metadata' => null,
        ];
    }

    /**
     * Indicate that the subscription is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'starts_at' => now()->subDays(30),
            'ends_at' => now()->addDays(30),
            'trial_ends_at' => null,
        ]);
    }

    /**
     * Indicate that the subscription is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'starts_at' => now()->subDays(60),
            'ends_at' => now()->subDays(1),
            'trial_ends_at' => null,
        ]);
    }

    /**
     * Indicate that the subscription is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'starts_at' => now()->subDays(60),
            'ends_at' => now()->addDays(30),
            'trial_ends_at' => null,
        ]);
    }

    /**
     * Indicate that the subscription is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'starts_at' => null,
            'ends_at' => null,
            'trial_ends_at' => null,
        ]);
    }
}
