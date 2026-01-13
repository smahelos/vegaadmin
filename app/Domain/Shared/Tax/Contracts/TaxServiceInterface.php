<?php

namespace App\Domain\Shared\Tax\Contracts;

/**
 * Contract for accessing tax metadata for forms and calculations.
 */
interface TaxServiceInterface
{
    /**
     * Get all taxes keyed by slug for selection where slug is stable identifier.
     *
     * @return array<string,string> slug => name
     */
    public function getAllTaxes(): array;

    /**
     * Get all taxes keyed by numeric id for standard select fields.
     *
     * @return array<int,string> id => name
     */
    public function getAllTaxesForSelect(): array;
}
