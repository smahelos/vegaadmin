<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Business;
use Closure;

trait DocumentParties
{
    /**
     * The document's business must belong to the document's user (composite foreign key).
     */
    private function businessOfUser(): Closure
    {
        return fn (array $attributes): int => Business::factory()->create(['user_id' => $attributes['user_id']])->id;
    }

    /**
     * @return array<string, string|null>
     */
    private function party(string $role): array
    {
        return [
            $role . '_name' => $this->faker->company(),
            $role . '_company_number' => null,
            $role . '_registration_country' => null,
            $role . '_vat_number' => null,
            $role . '_street' => $this->faker->streetAddress(),
            $role . '_city' => $this->faker->city(),
            $role . '_postal_code' => $this->faker->numerify('### ##'),
            $role . '_country' => 'CZ',
            $role . '_email' => $this->faker->safeEmail(),
            $role . '_phone' => null,
        ];
    }
}
