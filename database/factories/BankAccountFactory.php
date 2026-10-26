<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BankAccount;
use App\Models\Business;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BankAccount>
 */
final class BankAccountFactory extends Factory
{
    /**
     * Valid Czech accounts: local number and bank code with the IBAN computed from them.
     *
     * @var list<array{number: string, bank_code: string, iban: string, swift: string, bank_name: string}>
     */
    private const CZECH_ACCOUNTS = [
        [
            'number' => '2001234561',
            'bank_code' => '2010',
            'iban' => 'CZ1520100000002001234561',
            'swift' => 'FIOBCZPP',
            'bank_name' => 'Fio banka',
        ],
        [
            'number' => '1234567899',
            'bank_code' => '0100',
            'iban' => 'CZ9501000000001234567899',
            'swift' => 'KOMBCZPP',
            'bank_name' => 'Komerční banka',
        ],
        [
            'number' => '2000145399',
            'bank_code' => '0800',
            'iban' => 'CZ7908000000002000145399',
            'swift' => 'GIBACZPX',
            'bank_name' => 'Česká spořitelna',
        ],
    ];

    protected $model = BankAccount::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'contact_id' => null,
            'label' => null,
            ...$this->faker->randomElement(self::CZECH_ACCOUNTS),
            'country' => 'CZ',
        ];
    }

    /**
     * An account belongs to exactly one owner, so the other owner column is cleared.
     */
    public function ownedBy(Business|Contact $owner): static
    {
        return $this->state(fn (): array => [
            'business_id' => $owner instanceof Business ? $owner->id : null,
            'contact_id' => $owner instanceof Contact ? $owner->id : null,
        ]);
    }
}
