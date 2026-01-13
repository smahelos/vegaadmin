<?php

namespace Database\Factories;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        $rand = random_int(1000,9999);
        return [
            'name' => 'Supplier '.$rand,
            'email' => 'supplier'.$rand.'@example.test',
            'phone' => '+420'.random_int(100000000,999999999),
            'street' => 'Street '.$rand,
            'city' => 'City'.$rand,
            'zip' => str_pad((string)random_int(10000,99999),5,'0',STR_PAD_LEFT),
            'country' => 'CZ',
            'ico' => (string)random_int(10000000,99999999),
            'dic' => 'CZ'.random_int(10000000,99999999),
            'description' => 'Test supplier '.$rand,
            'is_default' => false,
            'user_id' => User::factory(),
            'account_number' => (string)random_int(100000000,999999999),
            'bank_code' => (string)random_int(1000,9999),
            'iban' => null,
            'swift' => null,
            'bank_name' => 'Test Bank',
            'has_payment_info' => true,
        ];
    }

    /**
     * Indicate that the supplier should be the default.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }

    /**
     * Indicate that the supplier has complete payment info.
     */
    public function withPaymentInfo(): static
    {
        return $this->state(fn (array $attributes) => [
            'account_number' => $this->faker->numerify('##########'),
            'bank_code' => $this->faker->numerify('####'),
            'has_payment_info' => true,
        ]);
    }

    /**
     * Indicate that the supplier has IBAN payment info.
     */
    public function withIban(): static
    {
        return $this->state(fn (array $attributes) => [
            'iban' => $this->faker->iban(),
            'swift' => $this->faker->swiftBicNumber(),
            'has_payment_info' => true,
        ]);
    }
}
