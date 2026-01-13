<?php

namespace App\Domain\Party\Validation;

use App\Domain\Party\Contracts\PartyCreationValidatorInterface;
use App\Domain\Party\Exceptions\PartyCreationException;
use App\Domain\User\ValueObjects\UserId;

class PartyCreationValidator implements PartyCreationValidatorInterface
{
    public function validateClient(UserId $userId, array $data): array
    {
        $prefixed = isset($data['client_name']) || isset($data['client_email']);
        $name = $prefixed ? ($data['client_name'] ?? '') : ($data['name'] ?? '');
        if (empty($name) || mb_strlen($name) < 3) {
            throw new PartyCreationException('Client name missing or too short for creation.');
        }
        return [
            'name' => $name,
            'street' => $prefixed ? ($data['client_street'] ?? '') : ($data['street'] ?? ''),
            'city' => $prefixed ? ($data['client_city'] ?? '') : ($data['city'] ?? ''),
            'zip' => $prefixed ? ($data['client_zip'] ?? '') : ($data['zip'] ?? ''),
            'country' => $prefixed ? ($data['client_country'] ?? 'CZ') : ($data['country'] ?? 'CZ'),
            'ico' => $prefixed ? ($data['client_ico'] ?? '') : ($data['ico'] ?? ''),
            'dic' => $prefixed ? ($data['client_dic'] ?? '') : ($data['dic'] ?? ''),
            'email' => $prefixed ? ($data['client_email'] ?? '') : ($data['email'] ?? ''),
            'phone' => $prefixed ? ($data['client_phone'] ?? '') : ($data['phone'] ?? ''),
            'description' => $prefixed ? ($data['client_description'] ?? '') : ($data['description'] ?? ''),
            'is_default' => $prefixed ? ($data['client_is_default'] ?? false) : ($data['is_default'] ?? false),
            'user_id' => $userId->toInt(),
        ];
    }

    public function validateSupplier(UserId $userId, array $data): array
    {
        $name = $data['name'] ?? '';
        if (empty($name) || mb_strlen($name) < 3) {
            throw new PartyCreationException('Supplier name missing or too short for creation.');
        }
        return [
            'name' => $name,
            'street' => $data['street'] ?? '',
            'city' => $data['city'] ?? '',
            'zip' => $data['zip'] ?? '',
            'country' => $data['country'] ?? 'CZ',
            'ico' => $data['ico'] ?? '',
            'dic' => $data['dic'] ?? '',
            'email' => $data['email'] ?? '',
            'phone' => $data['phone'] ?? '',
            'description' => $data['description'] ?? '',
            'account_number' => $data['account_number'] ?? '',
            'bank_code' => $data['bank_code'] ?? '',
            'bank_name' => $data['bank_name'] ?? '',
            'iban' => $data['iban'] ?? '',
            'swift' => $data['swift'] ?? '',
            // Keep null when no logo provided; tests expect null not empty string
            'supplier_logo' => $data['supplier_logo'] ?? null,
            'has_payment_info' => (!empty($data['account_number']) && !empty($data['bank_code'])),
            'is_default' => $data['is_default'] ?? false,
            'user_id' => $userId->toInt(),
        ];
    }
}
