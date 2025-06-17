<?php

namespace Database\Factories;

use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentMethod>
 */
class PaymentMethodFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = PaymentMethod::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->randomElement([
            'Cash',
            'Credit Card',
            'Debit Card', 
            'Bank Transfer',
            'PayPal',
            'Stripe',
            'Bitcoin',
            'Check',
            'Wire Transfer',
            'Online Payment'
        ]);
        
        return [
            'name' => $name,
            'slug' => $this->faker->unique()->slug(),
            'country' => $this->faker->optional()->countryCode(),
            'currency' => $this->faker->optional()->currencyCode(),
            'icon' => $this->faker->optional()->word(),
        ];
    }

    /**
     * Create a cash payment method.
     */
    public function cash(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Cash',
            'slug' => 'cash',
            'description' => 'Payment in cash',
            'is_active' => true,
        ]);
    }

    /**
     * Create a credit card payment method.
     */
    public function creditCard(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Credit Card',
            'slug' => 'credit-card',
            'description' => 'Payment by credit card',
            'is_active' => true,
        ]);
    }

    /**
     * Create a bank transfer payment method.
     */
    public function bankTransfer(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Bank Transfer',
            'slug' => 'bank-transfer',
            'description' => 'Payment by bank transfer',
            'is_active' => true,
        ]);
    }

    /**
     * Create a PayPal payment method.
     */
    public function paypal(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'PayPal',
            'slug' => 'paypal',
            'description' => 'Payment via PayPal',
            'is_active' => true,
        ]);
    }

    /**
     * Create a payment method with a specific name.
     */
    public function withName(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name),
        ]);
    }
}
