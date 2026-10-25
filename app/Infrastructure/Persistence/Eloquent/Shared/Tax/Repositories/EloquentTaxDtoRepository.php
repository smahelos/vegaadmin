<?php

namespace App\Infrastructure\Persistence\Eloquent\Shared\Tax\Repositories;

use App\Domain\Shared\Tax\Contracts\TaxDtoReadRepositoryInterface;
use App\Domain\Shared\Tax\Contracts\TaxDtoWriteRepositoryInterface;
use App\Domain\Shared\Tax\DTO\TaxDTO;
use App\Domain\Shared\Tax\DTO\TaxWriteData;
use App\Infrastructure\Persistence\Eloquent\Shared\Tax\Mappers\EloquentTaxMapper;

/**
 * Eloquent implementation of Tax DTO repository.
 * Delegates to existing EloquentTaxRepository and maps results to DTOs.
 */
class EloquentTaxDtoRepository implements TaxDtoReadRepositoryInterface, TaxDtoWriteRepositoryInterface
{
    public function __construct(
        private readonly EloquentTaxRepository $taxRepository,
        private readonly EloquentTaxMapper $mapper,
    ) {}

    public function getAllTaxesForSelect(): array
    {
        return $this->taxRepository->getAllTaxesForSelect();
    }

    public function getDphRatesForDropdown(): array
    {
        return $this->taxRepository->getDphRatesForDropdown();
    }

    public function create(TaxWriteData $writeData): TaxDTO
    {
        $eloquentTax = $this->taxRepository->create($writeData->toArray());

        return $this->mapper->toDto($eloquentTax);
    }
}
