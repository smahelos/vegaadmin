<?php

namespace App\Domain\Shared\Tax\Contracts;

use App\Domain\Shared\Tax\DTO\TaxDTO;
use App\Domain\Shared\Tax\ValueObjects\TaxId;

/**
 * Read-only repository interface for Tax DTOs.
 * Provides clean Domain layer access to tax data.
 */
interface TaxDtoReadRepositoryInterface
{
    /**
     * Get all taxes as DTOs.
     */
    public function getAllTaxes(): array;

    /**
     * Get taxes formatted for dropdown (id => rate).
     */
    public function getAllTaxesForSelect(): array;

    /**
     * Get DPH tax rates formatted for dropdown (id => rate).
     */
    public function getDphRatesForDropdown(): array;

    /**
     * Find tax by ID.
     */
    public function findById(TaxId $id): ?TaxDTO;

    /**
     * Find tax by slug.
     */
    public function findBySlug(string $slug): ?TaxDTO;
}
