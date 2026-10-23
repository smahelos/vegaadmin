<?php

namespace Database\Factories;

use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @extends Factory<PaymentMethod>
 */
class PaymentMethodFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Model>
     */
    protected $model = PaymentMethod::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $options = [
            'Cash',
            'Card',
            'Bank Transfer',
            'PayPal',
        ];
        $name = $options[array_rand($options)];
        $slug = Str::slug($name . '-' . uniqid());

        return [
            'name' => $name,
            'slug' => $slug,
            'country' => 'CZ',
            'currency' => 'CZK',
            'icon' => null,
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
        ]);
    }

    /**
     * Create a payment method with a specific name.
     */
    public function withName(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
            'slug' => Str::slug($name),
        ]);
    }
}
