<?php

namespace App\Domain\Party\DTO;

class SupplierDTO
{
    public function __construct(
        public readonly int $id,
        public readonly ?string $name,
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly ?string $street,
        public readonly ?string $city,
        public readonly ?string $zip,
        public readonly ?string $country,
        public readonly ?string $ico,
        public readonly ?string $dic,
        public readonly ?string $description,
        public readonly ?string $created_at,
        public readonly ?string $supplier_logo,
        public readonly ?int $account_number,
        public readonly ?string $bank_code,
        public readonly ?string $bank_name,
        public readonly ?string $iban,
        public readonly ?string $swift,
        public readonly ?int $user_id,
        public readonly bool $is_default,
    ) {}
}
