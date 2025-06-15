<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        return [
            'invoice_vs' => $this->faker->unique()->numerify('INV-####'),
            'issue_date' => $this->faker->date(),
            'tax_point_date' => $this->faker->date(),
            'due_in' => $this->faker->numberBetween(14, 30),
            'client_id' => Client::factory(),
            'user_id' => User::factory(),
            'payment_status_id' => $this->faker->randomElement([1, 2, 3, 4, 5]), // Assuming these are valid IDs for payment statuses
            'payment_amount' => $this->faker->randomFloat(2, 100, 10000),
            'payment_currency' => $this->faker->currencyCode(),
            'invoice_text' => $this->faker->optional()->sentence(),
            'payment_method_id' => $this->faker->optional()->numberBetween(1, 2),
        ];
    }
}
