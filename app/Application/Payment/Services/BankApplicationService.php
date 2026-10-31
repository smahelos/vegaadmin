<?php

declare(strict_types=1);

namespace App\Application\Payment\Services;

use App\Domain\Payment\Contracts\BankDtoRepository;
use App\Domain\Payment\DTO\BankDTO;
use App\Domain\Shared\Geography\Enums\Country;

/**
 * The banks a bank code is chosen from in bank account forms.
 */
final class BankApplicationService
{
    public function __construct(
        private readonly BankDtoRepository $banks,
    ) {}

    /**
     * @return list<BankDTO>
     */
    public function listActive(Country $country): array
    {
        return $this->banks->listActiveForCountry($country);
    }
}
