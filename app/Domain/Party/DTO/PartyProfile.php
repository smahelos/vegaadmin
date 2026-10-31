<?php

declare(strict_types=1);

namespace App\Domain\Party\DTO;

use App\Domain\Party\ValueObjects\Address;
use App\Domain\Party\ValueObjects\RegistrationNumber;
use App\Domain\Party\ValueObjects\VatNumber;

/**
 * The details businesses and contacts share. Null means empty: an update stores it as empty.
 */
final readonly class PartyProfile
{
    public function __construct(
        public string $name,
        public ?string $shortName,
        public ?RegistrationNumber $registrationNumber,
        public ?VatNumber $vatNumber,
        public ?string $email,
        public ?string $phone,
        public ?string $note,
        public ?Address $address,
    ) {}
}
