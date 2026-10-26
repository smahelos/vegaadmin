<?php

namespace App\Infrastructure\Persistence\Eloquent\Payment\Repositories;

use App\Domain\Payment\Contracts\BankDtoRepository;
use App\Domain\Payment\DTO\BankDTO;
use App\Domain\Shared\Geography\Enums\Country;
use App\Infrastructure\Persistence\Eloquent\Payment\Mappers\EloquentBankMapper;
use App\Models\Bank;

class EloquentBankDtoRepository implements BankDtoRepository
{
    public function __construct(
        private readonly EloquentBankRepository $banksRepository,
        private readonly EloquentBankMapper $mapper,
    ) {}

    /**
     * Get all banks
     */
    public function getAllBanks(
        string $country = 'CZ',
        string $orderBy = 'created_at',
        string $orderDirection = 'asc',
    ): array {
        $banks = $this->banksRepository->getAllBanks(
            $country,
            $orderBy,
            $orderDirection,
        );

        return array_map(fn ($bank) => $this->mapper->toDto($bank), $banks);
    }

    public function listActiveForCountry(Country $country): array
    {
        return Bank::query()
            ->where('country', $country->value)
            ->where('active', true)
            ->orderBy('code')
            ->get()
            ->map(fn (Bank $bank): BankDTO => $this->mapper->toDto($bank))
            ->values()
            ->all();
    }
}
