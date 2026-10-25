<?php

namespace App\Domain\Shared\Tax\Contracts;

use App\Domain\Shared\Tax\DTO\TaxDTO;
use App\Domain\Shared\Tax\DTO\TaxWriteData;

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
}
