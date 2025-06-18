<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Client;
use App\Models\User;
use App\Models\Supplier;
use App\Models\PaymentMethod;
use App\Models\Status;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        return [
            'invoice_vs' => $this->faker->unique()->numerify('INV-####'),
            'invoice_ks' => $this->faker->optional()->numerify('####'),
            'invoice_ss' => $this->faker->optional()->numerify('####'),
            'issue_date' => $this->faker->date(),
            'tax_point_date' => $this->faker->date(),
            'due_in' => $this->faker->numberBetween(14, 30),
            'client_id' => Client::factory(),
            'user_id' => User::factory(),
            'supplier_id' => Supplier::factory(),
            'payment_status_id' => Status::factory(),
            'payment_method_id' => PaymentMethod::factory(),
            'payment_amount' => $this->faker->randomFloat(2, 100, 10000),
            'payment_currency' => $this->faker->currencyCode(),
            'invoice_text' => $this->faker->optional()->sentence(),
        ];
    }
}
