<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->company(),
            'email' => $this->faker->unique()->safeEmail(),
            'street' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'zip' => $this->faker->postcode(),
            'country' => $this->faker->countryCode(),
            'ico' => $this->faker->optional()->numerify('########'),
            'dic' => $this->faker->optional()->numerify('CZ########'),
            'phone' => $this->faker->optional()->phoneNumber(),
            'description' => $this->faker->optional()->sentence(),
            'is_default' => false,
        ];
    }
}
