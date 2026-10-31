<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contact>
 */
final class ContactFactory extends Factory
{
    use CzechCompanyNumbers;

    protected $model = Contact::class;

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
            'contact_person' => null,
            'note' => null,
        ];
    }

    public function archived(): static
    {
        return $this->state(fn (): array => ['archived_at' => now()]);
    }
}
