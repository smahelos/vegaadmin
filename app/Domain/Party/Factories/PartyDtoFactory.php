<?php

namespace App\Domain\Party\Factories;

use App\Domain\Party\DTO\SupplierDTO;
use App\Domain\Party\DTO\SupplierWriteData;
use App\Domain\Party\DTO\ClientDTO;
use App\Domain\Party\DTO\ClientWriteData;

/**
 * Domain Factory for creating Party DTOs from raw data.
 * Replaces the reflection-based buildDto approach with explicit construction.
 */
class PartyDtoFactory
{
    public static function createSupplierWriteData(array $data): SupplierWriteData
    {
        return new SupplierWriteData(
            name: $data['name'] ?? null,
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            street: $data['street'] ?? null,
            city: $data['city'] ?? null,
            zip: $data['zip'] ?? null,
            country: $data['country'] ?? null,
            ico: $data['ico'] ?? null,
            dic: $data['dic'] ?? null,
            shortcut: $data['shortcut'] ?? null,
            description: $data['description'] ?? null,
            supplier_logo: $data['supplier_logo'] ?? null,
            account_number: $data['account_number'] ?? null,
            bank_code: $data['bank_code'] ?? null,
            bank_name: $data['bank_name'] ?? null,
            iban: $data['iban'] ?? null,
            swift: $data['swift'] ?? null,
            has_payment_info: $data['has_payment_info'] ?? null,
            user_id: isset($data['user_id']) ? (int) $data['user_id'] : null,
            is_default: (bool) ($data['is_default'] ?? false),
        );
    }

    public static function createClientWriteData(array $data): ClientWriteData
    {
        return new ClientWriteData(
            name: $data['name'] ?? null,
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            street: $data['street'] ?? null,
            city: $data['city'] ?? null,
            zip: $data['zip'] ?? null,
            country: $data['country'] ?? null,
            ico: $data['ico'] ?? null,
            dic: $data['dic'] ?? null,
            shortcut: $data['shortcut'] ?? null,
            description: $data['description'] ?? null,
            user_id: isset($data['user_id']) ? (int) $data['user_id'] : null,
            is_default: (bool) ($data['is_default'] ?? false),
        );
    }
}
