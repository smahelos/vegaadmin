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
        return [
            'name' => $this->faker->company(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'street' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'zip' => $this->faker->postcode(),
            'country' => $this->faker->randomElement(['CZ', 'SK', 'DE', 'AT']),
            'ico' => $this->faker->optional()->numerify('########'),
            'dic' => $this->faker->optional()->regexify('CZ[0-9]{8,10}'),
            'description' => $this->faker->optional()->sentence(),
            'is_default' => false,
            'user_id' => User::factory(),
            'account_number' => $this->faker->optional()->numerify('##########'),
            'bank_code' => $this->faker->optional()->numerify('####'),
            'iban' => $this->faker->optional()->iban(),
            'swift' => $this->faker->optional()->swiftBicNumber(),
            'bank_name' => $this->faker->optional()->randomElement(['Česká spořitelna', 'ČSOB', 'Komerční banka', 'UniCredit Bank']),
            'has_payment_info' => $this->faker->boolean(),
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
