<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Party\Mappers;

use App\Domain\Party\DTO\PartyProfile;
use App\Domain\Party\ValueObjects\Address;
use App\Domain\Party\ValueObjects\RegistrationNumber;
use App\Domain\Party\ValueObjects\VatNumber;
use App\Domain\Shared\Geography\Enums\Country;
use App\Models\Address as AddressModel;
use App\Models\Business;
use App\Models\Contact;

/**
 * The columns businesses and contacts share, mapped once for both.
 */
final class EloquentPartyProfileMapper
{
    /**
     * Expects the address relation to be loaded.
     */
    public static function toProfile(Business|Contact $party): PartyProfile
    {
        return new PartyProfile(
            name: $party->name,
            shortName: $party->short_name,
            registrationNumber: RegistrationNumber::fromParts(
                self::country($party->registration_country),
                $party->company_number,
            ),
            vatNumber: VatNumber::fromNullable($party->vat_number),
            email: $party->email,
            phone: $party->phone,
            note: $party->note,
            address: self::toAddress($party->address),
        );
    }

    /**
     * @return array<string, string|null>
     */
    public static function toAttributes(PartyProfile $profile): array
    {
        return [
            'name' => $profile->name,
            'short_name' => $profile->shortName,
            'registration_country' => $profile->registrationNumber?->country->value,
            'company_number' => $profile->registrationNumber?->number,
            'vat_number' => $profile->vatNumber?->value,
            'email' => $profile->email,
            'phone' => $profile->phone,
            'note' => $profile->note,
        ];
    }

    /**
     * @return array<string, string|null>
     */
    public static function toAddressAttributes(Address $address): array
    {
        return [
            'street' => $address->street,
            'city' => $address->city,
            'postal_code' => $address->postalCode,
            'country' => $address->country->value,
        ];
    }

    private static function toAddress(?AddressModel $address): ?Address
    {
        if ($address === null) {
            return null;
        }

        return Address::fromParts(
            $address->street,
            $address->city,
            $address->postal_code,
            self::country($address->country),
        );
    }

    private static function country(?string $code): ?Country
    {
        return $code === null ? null : Country::from($code);
    }
}
