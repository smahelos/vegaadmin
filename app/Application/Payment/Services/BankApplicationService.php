<?php

namespace App\Application\Payment\Services;

use App\Application\Payment\Contracts\BankApplicationServiceInterface;
use App\Domain\Payment\Contracts\BankServiceInterface;

class BankApplicationService implements BankApplicationServiceInterface
{
    public function __construct(private readonly BankServiceInterface $bankService)
    {
    }

    public function getBanksForDropdown(): array
    {
        return $this->bankService->getBanksForDropdown();
    }

    public function getBanksForJs(): array
    {
        return $this->bankService->getBanksForJs();
    }
}
