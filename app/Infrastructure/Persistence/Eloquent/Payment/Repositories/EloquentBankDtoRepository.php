<?php

namespace App\Infrastructure\Persistence\Eloquent\Payment\Repositories;

use App\Domain\Payment\Contracts\BankDtoRepository;
use App\Infrastructure\Persistence\Eloquent\Payment\Mappers\EloquentBankMapper;
use App\Domain\Payment\DTO\BankDTO;

class EloquentBankDtoRepository implements BankDtoRepository
{
    public function __construct(
        private readonly EloquentBankRepository $banksRepository,
        private readonly EloquentBankMapper $mapper
    ) {
    }

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
        $banks = $this->banksRepository->getAllBanks($country, $orderBy, $orderDirection);

        return array_map(fn($bank) => $this->mapper->toDto($bank), $banks);
    }
}
