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
        private readonly EloquentBankMapper $mapper,
    ) {}

    public function listActiveForCountry(Country $country): array
    {
        $query = Bank::query();
        $query->where('country', $country->value);
        $query->where('active', true);
        $query->orderBy('code');
        $banks = $query->get();
        $banks = $banks->map(
            fn (Bank $bank): BankDTO => $this->mapper->toDto($bank),
        );
        $banks = $banks->values();

        return $banks->all();
    }
}
