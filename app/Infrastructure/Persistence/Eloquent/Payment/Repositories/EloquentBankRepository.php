<?php

namespace App\Infrastructure\Persistence\Eloquent\Payment\Repositories;

use App\Models\Bank;

class EloquentBankRepository
{
    /**
     * Get all banks
     * 
     * @param string $country
     * @param string $orderBy
     * @param string $orderDirection
     * @return array
     */
    public function getAllBanks(string $country = 'CZ', string $orderBy = 'created_at', string $orderDirection = 'asc'): array
    {
        return Bank::where('country', $country)
            ->orderBy($orderBy, $orderDirection)
            ->get()
            ->all();
    }
}
