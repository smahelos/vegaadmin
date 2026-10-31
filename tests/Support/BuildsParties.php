<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Party\DTO\BankAccountWriteData;
use App\Domain\Party\DTO\PartyProfile;
use App\Domain\Party\ValueObjects\Address;
use App\Domain\Party\ValueObjects\RegistrationNumber;
use App\Domain\Payment\ValueObjects\BankAccount;
use App\Domain\Shared\Geography\Enums\Country;
use App\Domain\User\ValueObjects\UserId;
use App\Models\EntityLimit;
use App\Models\User;

/**
 * Owners with the plan role that carries the business and contact limits, and party data for the services.
 */
trait BuildsParties
{
    private function frontendUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('frontend_user');

        return $user;
    }

    private function idOf(User $user): UserId
    {
        return UserId::fromInt($user->id);
    }

    /**
     * Changes the seeded capacity of the frontend_user plan within the test transaction.
     */
    private function limitActive(string $entityType, int $limit): void
    {
        EntityLimit::query()
            ->where('entity_type', $entityType)
            ->where('metric_type', 'active')
            ->update(['limit_value' => $limit]);
    }

    private function profile(
        string $name = 'Acme s.r.o.',
        ?string $companyNumber = null,
        Country $country = Country::CZ,
        ?string $email = null,
        ?Address $address = null,
    ): PartyProfile {
        return new PartyProfile(
            name: $name,
            shortName: null,
            registrationNumber: RegistrationNumber::fromParts($country, $companyNumber),
            vatNumber: null,
            email: $email,
            phone: null,
            note: null,
            address: $address,
        );
    }

    private function brnoAddress(): Address
    {
        return Address::fromParts(
            'Hlavní 1',
            'Brno',
            '602 00',
            Country::CZ,
        ) ?? $this->fail('The address is empty');
    }

    private function czechAccount(string $number = '2001234561', string $bankCode = '2010'): BankAccountWriteData
    {
        return new BankAccountWriteData(
            null,
            BankAccount::fromParts(
                null,
                $number,
                $bankCode,
                Country::CZ,
                null,
            ),
            null,
        );
    }
}
