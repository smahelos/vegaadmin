<?php

namespace App\Infrastructure\Persistence\Eloquent\Payment\Mappers;
    
use App\Domain\Payment\DTO\BankDTO;
use App\Models\Bank;

/**
 * Maps between Eloquent Bank models and Bank DTOs.
 * Isolates Domain layer from Eloquent implementation details.
 */
class EloquentBankMapper
{
    /**
     * Convert Eloquent Bank model to BankDTO.
     */
    public function toDto(Bank $model): BankDTO
    {
        return BankDTO::fromArray([
            'id' => $model->getAttribute('id'),
            'name' => $model->getAttribute('name'),
            'code' => $model->getAttribute('code'),
            'swift' => $model->getAttribute('swift'),
            'country' => $model->getAttribute('country'),
            'active' => $model->getAttribute('active'),
            'description' => $model->getAttribute('description'),
            'created_at' => $model->getAttribute('created_at'),
        ]);
    }

    /**
     * Convert BankDTO to array for Eloquent model creation/update.
     */
    public function fromDto(BankDTO $dto): array
    {
        return [
            'id' => $dto->id,
            'name' => $dto->name,
            'code' => $dto->code,
            'swift' => $dto->swift,
            'country' => $dto->country,
            'active' => $dto->active,
            'description' => $dto->description,
            'created_at' => $dto->created_at,
        ];
    }
}
