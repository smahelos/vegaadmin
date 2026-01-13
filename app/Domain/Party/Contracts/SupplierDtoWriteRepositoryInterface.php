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

    public function unsetOthersDefault(PartyId $currentId, int $userId): void;

    /** Update supplier by ID with provided attributes */
    public function updateById(PartyId $id, SupplierWriteData $data): bool;

    /** Delete supplier by ID */
    public function deleteById(PartyId $id): bool;

    /** Check if supplier has linked invoices */
    public function hasLinkedInvoices(PartyId $id): bool;
}
