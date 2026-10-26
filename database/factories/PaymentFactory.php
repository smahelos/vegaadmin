<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Payment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = $this->faker->randomElement([
            'pending',
            'processing',
            'completed',
            'failed',
            'cancelled',
            'refunded',
        ]);
        $gateways = [
            'gopay',
            'paypal',
            'stripe',
        ];
        $methods = [
            'card',
            'bank_transfer',
            'paypal',
        ];

        return [
            'subscription_id' => Subscription::factory(),
            'gateway' => $this->faker->randomElement($gateways),
            'gateway_payment_id' => 'GP' . $this->faker->numerify('#########'),
            'status' => $status,
            'amount' => $this->faker->randomFloat(
                2,
                9.99,
                199.99,
            ),
            'currency' => 'EUR',
            'payment_method' => $this->faker->randomElement($methods),
            'gateway_data' => [
                'transaction_id' => 'TXN' . $this->faker->numerify('########'),
                'gateway_reference' => $this->faker->uuid,
                'payment_date' => $this->faker->dateTime->format('Y-m-d H:i:s'),
            ],
            'failure_reason' => $status === 'failed' ? $this->faker->sentence(3) : null,
        ];
    }
}
