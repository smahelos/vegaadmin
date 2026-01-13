<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
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
        $status = $this->faker->randomElement(['pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded']);
        $gateways = ['gopay', 'paypal', 'stripe'];
        $methods = ['card', 'bank_transfer', 'paypal'];
        
        return [
            'subscription_id' => Subscription::factory(),
            'gateway' => $this->faker->randomElement($gateways),
            'gateway_payment_id' => 'GP' . $this->faker->numerify('#########'),
            'status' => $status,
            'amount' => $this->faker->randomFloat(2, 9.99, 199.99),
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

    /**
     * Indicate that the payment is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'failure_reason' => null,
            'gateway_data' => array_merge($attributes['gateway_data'] ?? [], [
                'completed_at' => now()->toISOString(),
            ]),
        ]);
    }

    /**
     * Indicate that the payment is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'failure_reason' => null,
        ]);
    }

    /**
     * Indicate that the payment is failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'failure_reason' => $this->faker->randomElement([
                'Card declined',
                'Insufficient funds',
                'Card expired',
                'Invalid card number',
                'Payment timeout',
            ]),
        ]);
    }

    /**
     * Indicate that the payment is processing.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
            'failure_reason' => null,
        ]);
    }

    /**
     * Indicate that the payment is refunded.
     */
    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'refunded',
            'failure_reason' => null,
            'gateway_data' => array_merge($attributes['gateway_data'] ?? [], [
                'refunded_at' => now()->toISOString(),
                'refund_amount' => $attributes['amount'] ?? 0,
            ]),
        ]);
    }

    /**
     * Indicate that the payment uses GoPay gateway.
     */
    public function gopay(): static
    {
        return $this->state(fn (array $attributes) => [
            'gateway' => 'gopay',
            'gateway_payment_id' => 'GP' . $this->faker->numerify('#########'),
            'payment_method' => 'card',
        ]);
    }

    /**
     * Indicate that the payment uses PayPal gateway.
     */
    public function paypal(): static
    {
        return $this->state(fn (array $attributes) => [
            'gateway' => 'paypal',
            'gateway_payment_id' => 'PP' . $this->faker->numerify('#########'),
            'payment_method' => 'paypal',
        ]);
    }
}
