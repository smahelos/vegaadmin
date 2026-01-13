<?php

namespace App\Domain\Shared\Tax\Contracts;

use App\Domain\Shared\Tax\DTO\TaxDTO;
use App\Domain\Shared\Tax\DTO\TaxWriteData;
use App\Domain\Shared\Tax\ValueObjects\TaxId;

/**
 * Write repository interface for Tax DTOs.
 * Handles tax creation, updates, and deletion.
 */
interface TaxDtoWriteRepositoryInterface
{
    /**
     * Create new tax.
     */
    public function create(TaxWriteData $writeData): TaxDTO;

    /**
     * Update existing tax.
     */
    public function update(TaxId $id, TaxWriteData $writeData): TaxDTO;

    /**
     * Delete tax by ID.
     */
    public function delete(TaxId $id): void;
}
