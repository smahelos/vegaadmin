<?php

namespace App\Domain\Party\Contracts;

use App\Domain\Party\DTO\ClientDTO;
use App\Domain\Party\ValueObjects\PartyId;

/**
 * Read repository that returns DTOs instead of Eloquent models.
 */
interface ClientDtoReadRepositoryInterface
{
    public function findById(PartyId $id): ?ClientDTO;
}
