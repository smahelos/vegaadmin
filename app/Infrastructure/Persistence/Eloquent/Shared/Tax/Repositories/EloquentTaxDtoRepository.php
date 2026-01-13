<?php

namespace App\Infrastructure\Persistence\Eloquent\Shared\Tax\Repositories;

use App\Domain\Shared\Tax\Contracts\TaxDtoReadRepositoryInterface;
use App\Domain\Shared\Tax\Contracts\TaxDtoWriteRepositoryInterface;
use App\Domain\Shared\Tax\DTO\TaxDTO;
use App\Domain\Shared\Tax\DTO\TaxWriteData;
use App\Domain\Shared\Tax\ValueObjects\TaxId;
use App\Infrastructure\Persistence\Eloquent\Shared\Tax\Mappers\EloquentTaxMapper;
use App\Infrastructure\Persistence\Eloquent\Shared\Tax\Repositories\EloquentTaxRepository;
use Illuminate\Support\Collection;

/**
 * Eloquent implementation of Tax DTO repository.
 * Delegates to existing EloquentTaxRepository and maps results to DTOs.
 */
class EloquentTaxDtoRepository implements TaxDtoReadRepositoryInterface, TaxDtoWriteRepositoryInterface
{
    public function __construct(
        private readonly EloquentTaxRepository $taxRepository,
        private readonly EloquentTaxMapper $mapper
    ) {
    }

    public function getAllTaxes(): array
    {
        return $this->taxRepository->getAllTaxes();
    }

    public function getAllTaxesForSelect(): array
    {
        return $this->taxRepository->getAllTaxesForSelect();
    }

    public function getDphRatesForDropdown(): array
    {
        return $this->taxRepository->getDphRatesForDropdown();
    }

    public function findById(TaxId $id): ?TaxDTO
    {
        $tax = $this->taxRepository->findById($id->getValue());
        
        return $tax ? $this->mapper->toDto($tax) : null;
    }

    public function findBySlug(string $slug): ?TaxDTO
    {
        $tax = $this->taxRepository->findBySlug($slug);
        
        return $tax ? $this->mapper->toDto($tax) : null;
    }

    public function create(TaxWriteData $writeData): TaxDTO
    {
        $eloquentTax = $this->taxRepository->create($writeData->toArray());
        
        return $this->mapper->toDto($eloquentTax);
    }

    public function update(TaxId $id, TaxWriteData $writeData): TaxDTO
    {
        $eloquentTax = $this->taxRepository->updateById($id->getValue(), $writeData->toArray());
        
        return $this->mapper->toDto($eloquentTax);
    }

    public function delete(TaxId $id): void
    {
        $this->taxRepository->deleteById($id->getValue());
    }
}
