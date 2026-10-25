<?php

namespace App\Domain\Shared\Tax\Contracts;

/**
 * Read-only repository interface for Tax DTOs.
 * Provides clean Domain layer access to tax data.
 */
interface TaxDtoReadRepositoryInterface
{
    /**
     * Get taxes formatted for dropdown (id => rate).
     */
    public function getAllTaxesForSelect(): array;

    /**
     * Get DPH tax rates formatted for dropdown (id => rate).
     */
    public function getDphRatesForDropdown(): array;
}
