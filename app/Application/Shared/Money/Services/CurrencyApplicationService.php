<?php

declare(strict_types=1);

namespace App\Application\Shared\Money\Services;

use App\Domain\Shared\Money\Contracts\CurrencyExchangeServiceInterface;

final class CurrencyApplicationService
{
    public function __construct(
        private readonly CurrencyExchangeServiceInterface $exchangeService,
    ) {}

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
