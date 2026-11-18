<?php

declare(strict_types=1);

namespace App\Application\Shared\Money\Services;

use App\Domain\Shared\Money\Contracts\CurrencyExchangeServiceInterface;
use App\Domain\Shared\Money\Contracts\CurrencyServiceInterface;

final class CurrencyApplicationService
{
    public function __construct(
        private readonly CurrencyServiceInterface $currencyService,
        private readonly CurrencyExchangeServiceInterface $exchangeService,
    ) {}

    /** @return array<string,string> */
    public function getCommonCurrencies(): array
    {
        return $this->currencyService->getCommonCurrencies();
    }

    /** @return array<string,string> */
    public function getAllCurrencies(): array
    {
        return $this->currencyService->getAllCurrencies();
    }

    public function getExchangeRate(string $from, string $to): ?float
    {
        return $this->exchangeService->getExchangeRate($from, $to);
    }

    public function convert(
        float $amount,
        string $from,
        string $to,
    ): ?float {
        return $this->exchangeService->convert(
            $amount,
            $from,
            $to,
        );
    }
}
