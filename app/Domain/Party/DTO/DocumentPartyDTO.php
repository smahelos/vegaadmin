<?php

declare(strict_types=1);

namespace App\Domain\Party\DTO;

use App\Domain\Party\ValueObjects\Address;
use App\Domain\Party\ValueObjects\RegistrationNumber;
use App\Domain\Party\ValueObjects\VatNumber;

/**
 * A supplier or customer as a document stores it: its own copy, which later changes of the business or contact
 * never reach. The short name, note and contact person are internal and never copied.
 */
final readonly class DocumentPartyDTO
{
    public function __construct(
        public string $name,
        public ?RegistrationNumber $registrationNumber,
        public ?VatNumber $vatNumber,
        public ?Address $address,
        public ?string $email,
        public ?string $phone,
    ) {}

    public static function fromProfile(PartyProfile $profile): self
    {
        return new self(
            $profile->name,
            $profile->registrationNumber,
            $profile->vatNumber,
            $profile->address,
            $profile->email,
            $profile->phone,
        );
    }

    /**
     * The party as the profile of a new contact, without the internal short name and note.
     */
    public function toProfile(): PartyProfile
    {
        return new PartyProfile(
            name: $this->name,
            shortName: null,
            registrationNumber: $this->registrationNumber,
            vatNumber: $this->vatNumber,
            email: $this->email,
            phone: $this->phone,
            note: null,
            address: $this->address,
        );
    }

    /**
     * Street, city, postal code and country, the address every invoice party needs.
     */
    public function hasCompleteAddress(): bool
    {
        return $this->address !== null &&
            $this->address->street !== null &&
            $this->address->city !== null &&
            $this->address->postalCode !== null;
    }

    /**
     * Whether the party is registered under the same number as the profile; two parties without a number are the
     * same, a number on one side only is a different identity.
     */
    public function hasIdentityOf(PartyProfile $profile): bool
    {
        if ($this->registrationNumber === null || $profile->registrationNumber === null) {
            return $this->registrationNumber === $profile->registrationNumber;
        }

        return $this->registrationNumber->equals($profile->registrationNumber);
    }
}
