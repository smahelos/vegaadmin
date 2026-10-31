<?php

namespace App\Domain\Payment\Contracts;

use App\Domain\Payment\DTO\BankDTO;
use App\Domain\Shared\Geography\Enums\Country;

interface BankDtoRepository
{
    /**
     * Banks of the country a bank code may be chosen from, ordered by code.
     *
     * @return list<BankDTO>
     */
    public function listActiveForCountry(Country $country): array;
}
