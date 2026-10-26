<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Address;
use App\Models\Business;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
final class AddressFactory extends Factory
{
    protected $model = Address::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'contact_id' => null,
            'street' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'postal_code' => $this->faker->numerify('### ##'),
            'country' => 'CZ',
        ];
    }
}
