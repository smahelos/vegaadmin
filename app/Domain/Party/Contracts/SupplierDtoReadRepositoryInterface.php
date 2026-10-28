<?php

namespace App\Domain\Party\Contracts;

use App\Domain\Party\DTO\SupplierDTO;
use App\Domain\Party\ValueObjects\PartyId;

/**
 * Read repository that returns DTOs instead of Eloquent models.
 */
interface SupplierDtoReadRepositoryInterface
{
    public function findById(PartyId $id): ?SupplierDTO;
}
