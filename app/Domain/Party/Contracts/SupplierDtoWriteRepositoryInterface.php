<?php

namespace App\Domain\Party\Contracts;

use App\Domain\Party\DTO\SupplierDTO;
use App\Domain\Party\DTO\SupplierWriteData;
use App\Domain\Party\ValueObjects\PartyId;

/**
 * Write repository that returns DTOs instead of Eloquent models.
 */
interface SupplierDtoWriteRepositoryInterface
{
    public function create(SupplierWriteData $data): SupplierDTO;

    /** Update supplier by ID with provided attributes */
    public function updateById(PartyId $id, SupplierWriteData $data): bool;

    /** Delete supplier by ID */
    public function deleteById(PartyId $id): bool;

    /** Whether invoices or expenses refer to the supplier, which keeps it from being deleted */
    public function hasLinkedDocuments(PartyId $id): bool;
}
