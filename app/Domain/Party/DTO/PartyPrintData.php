<?php

namespace App\Domain\Party\DTO;

/**
 * Domain DTO representing printable party data (supplier or client).
 */
class PartyPrintData
{
    public function __construct(
        public readonly ?string $name,
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly ?string $street,
        public readonly ?string $city,
        public readonly ?string $zip,
        public readonly ?string $country,
        public readonly ?string $ico,
        public readonly ?string $dic,
        public readonly ?string $supplier_logo = null,
        public readonly ?string $account_number = null,
        public readonly ?string $bank_code = null,
        public readonly ?string $bank_name = null,
        public readonly ?string $iban = null,
        public readonly ?string $swift = null,
    ) {}
}
