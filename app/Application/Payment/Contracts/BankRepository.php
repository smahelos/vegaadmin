<?php

namespace App\Application\Payment\Contracts;

interface BankRepository
{
    /**
     * Get all banks
     * 
     * @param string $country
     * @param string $orderBy
     * @param string $orderDirection
     * @return array
     */
    public function getAllBanks(string $country = 'CZ', string $orderBy = 'created_at', string $orderDirection = 'asc'): array;
}
