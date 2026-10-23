<?php

namespace App\Domain\Payment\Contracts;

interface BankDtoRepository
{
    /**
     * Get all banks
     */
    public function getAllBanks(
        string $country = 'CZ',
        string $orderBy = 'created_at',
        string $orderDirection = 'asc',
    ): array;
}
