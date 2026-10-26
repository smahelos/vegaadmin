<?php

namespace App\Domain\Party\Contracts;

use App\Domain\Party\DTO\SupplierDTO;
use App\Domain\Party\DTO\SupplierWriteData;

/**
 * Write repository that returns DTOs instead of Eloquent models.
 */
interface SupplierDtoWriteRepositoryInterface
{
    public function create(SupplierWriteData $data): SupplierDTO;
}
