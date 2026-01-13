<?php

namespace App\Application\Party\Mappers;

use App\Domain\Party\DTO\ClientDTO;
use Illuminate\Support\Collection;

/**
 * Mapper for transforming ClientDTO objects to HTTP-friendly arrays.
 * 
 * Centralizes data transformation logic to avoid duplication
 * in PartyApplicationService.
 */
class ClientArrayMapper
{
    /**
     * Map single ClientDTO to array format.
     */
    public function mapToArray(ClientDTO $dto): array
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
            'created_at' => $dto->created_at,
            'user_id' => $dto->user_id,
            'is_default' => $dto->is_default,
        ];
    }

    /**
     * Map collection of ClientDTOs to array format.
     */
    public function mapCollectionToArray(Collection $clients): array
    {
        return $clients->map(fn($dto) => $this->mapToArray($dto))->toArray();
    }
}
