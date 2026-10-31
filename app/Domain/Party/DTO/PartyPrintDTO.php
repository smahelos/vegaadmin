<?php

declare(strict_types=1);

namespace App\Domain\Party\DTO;

final readonly class PartyPrintDTO
{
    public function __construct(
        public string $name,
        public ?string $email,
        public ?string $phone,
        public ?string $street,
        public ?string $city,
        public ?string $postalCode,
        public ?string $country,
        public ?string $companyNumber,
        public ?string $vatNumber,
    ) {}

    public static function fromDocumentParty(DocumentPartyDTO $party): self
    {
        return new self(
            $party->name,
            $party->email,
            $party->phone,
            $party->address?->street,
            $party->address?->city,
            $party->address?->postalCode,
            $party->address?->country->value,
            $party->registrationNumber?->number,
            $party->vatNumber?->value,
        );
    }
}
