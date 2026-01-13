<?php

namespace App\Application\Shared\Money\Services;

use App\Application\Shared\Money\Contracts\CurrencyApplicationServiceInterface;
use App\Domain\Shared\Money\Contracts\CurrencyExchangeServiceInterface;
use App\Domain\Shared\Money\Contracts\CurrencyServiceInterface;

class CurrencyApplicationService implements CurrencyApplicationServiceInterface
{
    public function __construct(
        private readonly CurrencyServiceInterface $currencyService,
        private readonly CurrencyExchangeServiceInterface $exchangeService,
    ) {
    }

    public function getCommonCurrencies(): array
    {
        return $this->currencyService->getCommonCurrencies();
    }

    public function getAllCurrencies(): array
    {
        return $this->currencyService->getAllCurrencies();
    }

    public function getExchangeRate(string $from, string $to): ?float
    {
        return $this->exchangeService->getExchangeRate($from, $to);
    }

    public function convert(float $amount, string $from, string $to): ?float
    {
        return $this->exchangeService->convert($amount, $from, $to);
    }
}
