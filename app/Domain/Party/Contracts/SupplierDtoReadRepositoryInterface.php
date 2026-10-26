<?php

namespace App\Domain\Party\Contracts;

use App\Domain\Party\DTO\SupplierDTO;
use App\Domain\Party\ValueObjects\PartyId;
use App\Domain\User\ValueObjects\UserId;

/**
 * Read repository that returns DTOs instead of Eloquent models.
 */
interface SupplierDtoReadRepositoryInterface
{
    public function getDefaultSupplier(int $userId): ?SupplierDTO;

    public function findById(PartyId $id): ?SupplierDTO;

    /** Find by id scoped to a specific user */
    public function findByIdForUser(PartyId $id, UserId $userId): ?SupplierDTO;

    public function firstForUser(int $userId): ?SupplierDTO;
}
