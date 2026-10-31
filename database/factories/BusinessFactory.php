<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Business;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Business>
 */
final class BusinessFactory extends Factory
{
    use CzechCompanyNumbers;

    protected $model = Business::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->company(),
            'short_name' => null,
            'registration_country' => 'CZ',
            'company_number' => $this->czechCompanyNumber(random_int(0, 9_999_999)),
            'vat_number' => null,
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => null,
            'note' => null,
            'logo' => null,
        ];
    }

    public function archived(): static
    {
        return $this->state(fn (): array => ['archived_at' => now()]);
    }
}
