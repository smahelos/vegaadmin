<?php

declare(strict_types=1);

namespace App\Domain\Party\DTO;

use App\Domain\Party\ValueObjects\Address;
use App\Domain\Party\ValueObjects\VatNumber;

/**
 * A company as a public register records it, offered to prefill a form for the user to check; the VAT number
 * is only what the register holds, never derived from the company number.
 */
final readonly class RegisteredCompanyDTO
{
    public function __construct(
        public string $name,
        public ?VatNumber $vatNumber,
        public ?Address $address,
    ) {}
}
