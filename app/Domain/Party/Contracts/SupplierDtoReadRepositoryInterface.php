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
    /** @return array<int,string> */
    public function getSuppliersForDropdown(int $userId): array;

    public function getDefaultSupplier(int $userId): ?SupplierDTO;

    public function findById(PartyId $id): ?SupplierDTO;

    /** Find by id without scoping to current user (admin/ownership checks handled at higher layer) */
    public function findByIdAny(PartyId $id): ?SupplierDTO;

    /** Find by id scoped to a specific user */
    public function findByIdForUser(PartyId $id, UserId $userId): ?SupplierDTO;

    /** @return array<int, SupplierDTO> */
    public function allForAdmin(): array;

    /** @return array<int, SupplierDTO> */
    public function allForUser(int $userId): array;

    public function firstForUser(int $userId): ?SupplierDTO;
}
