<?php

namespace Database\Factories;

use App\Models\UserActivitySummary;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserActivitySummary>
 */
class UserActivitySummaryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = UserActivitySummary::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'user_name' => $this->faker->name(),
            'user_email' => $this->faker->unique()->safeEmail(),
            'total_invoices' => $this->faker->numberBetween(0, 100),
            'total_clients' => $this->faker->numberBetween(0, 50),
            'total_suppliers' => $this->faker->numberBetween(0, 30),
            'total_products' => $this->faker->numberBetween(0, 200),
            'last_invoice_date' => $this->faker->optional()->dateTimeBetween('-1 year'),
            'invoices_last_30_days' => $this->faker->numberBetween(0, 30),
            'invoices_last_7_days' => $this->faker->numberBetween(0, 7),
        ];
    }

    /**
     * State for high activity users.
     */
    public function highActivity(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'invoices_last_30_days' => $this->faker->numberBetween(21, 50),
                'invoices_last_7_days' => $this->faker->numberBetween(5, 15),
                'total_invoices' => $this->faker->numberBetween(50, 200),
                'last_invoice_date' => $this->faker->dateTimeBetween('-7 days'),
            ];
        });
    }

    /**
     * State for medium activity users.
     */
    public function mediumActivity(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'invoices_last_30_days' => $this->faker->numberBetween(5, 20),
                'invoices_last_7_days' => $this->faker->numberBetween(1, 5),
                'total_invoices' => $this->faker->numberBetween(10, 50),
                'last_invoice_date' => $this->faker->dateTimeBetween('-30 days'),
            ];
        });
    }

    /**
     * State for low activity users.
     */
    public function lowActivity(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'invoices_last_30_days' => $this->faker->numberBetween(1, 4),
                'invoices_last_7_days' => $this->faker->numberBetween(0, 2),
                'total_invoices' => $this->faker->numberBetween(1, 10),
                'last_invoice_date' => $this->faker->dateTimeBetween('-30 days'),
            ];
        });
    }

    /**
     * State for inactive users.
     */
    public function inactive(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'invoices_last_30_days' => 0,
                'invoices_last_7_days' => 0,
                'total_invoices' => $this->faker->numberBetween(0, 5),
                'last_invoice_date' => $this->faker->optional(0.3)->dateTimeBetween('-1 year', '-31 days'),
            ];
        });
    }
}
