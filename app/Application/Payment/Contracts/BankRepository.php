<?php

namespace App\Application\Payment\Contracts;

interface BankRepository
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
