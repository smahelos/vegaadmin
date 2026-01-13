<?php

namespace App\Application\Party\Mappers;

use App\Domain\Party\DTO\SupplierDTO;
use Illuminate\Support\Collection;

/**
 * Mapper for transforming SupplierDTO objects to HTTP-friendly arrays.
 * 
 * Centralizes data transformation logic to avoid duplication
 * in PartyApplicationService.
 */
class SupplierArrayMapper
{
    /**
     * Map single SupplierDTO to array format.
     */
    public function mapToArray(SupplierDTO $dto): array
    {
        return [
            'id' => $dto->id,
            'name' => $dto->name,
            'email' => $dto->email,
            'phone' => $dto->phone,
            'street' => $dto->street,
            'city' => $dto->city,
            'zip' => $dto->zip,
            'country' => $dto->country,
            'ico' => $dto->ico,
            'dic' => $dto->dic,
            'shortcut' => $dto->shortcut,
            'description' => $dto->description,
            'supplier_logo' => $dto->supplier_logo,
            'account_number' => $dto->account_number,
            'bank_code' => $dto->bank_code,
            'bank_name' => $dto->bank_name,
            'iban' => $dto->iban,
            'swift' => $dto->swift,
            'has_payment_info' => $dto->has_payment_info,
            'created_at' => $dto->created_at,
            'user_id' => $dto->user_id,
            'is_default' => $dto->is_default,
        ];
    }

    /**
     * Map collection of SupplierDTOs to array format.
     */
    public function mapCollectionToArray(Collection $suppliers): array
    {
        return $suppliers->map(fn($dto) => $this->mapToArray($dto))->toArray();
    }
}
