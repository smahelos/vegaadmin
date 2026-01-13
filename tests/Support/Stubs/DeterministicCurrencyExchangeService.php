<?php

namespace Tests\Support\Stubs;

use App\Domain\Shared\Money\Contracts\CurrencyExchangeServiceInterface;

/**
 * Deterministic stub for CurrencyExchangeServiceInterface used in feature tests.
 * Provides fixed, tiny curated rate table to ensure stable assertions.
 */
class DeterministicCurrencyExchangeService implements CurrencyExchangeServiceInterface
{
    /** @var array<string,float> */
    private array $baseRates = [
        'USD' => 1.0,
        'EUR' => 0.8,
        'CZK' => 20.0,
        'GBP' => 0.7,
    ];

    public function getExchangeRate(string $fromCurrency, string $toCurrency): ?float
    {
        $fromCurrency = strtoupper($fromCurrency);
        $toCurrency = strtoupper($toCurrency);
        if ($fromCurrency === $toCurrency) {
            return 1.0;
        }
        if (!isset($this->baseRates[$fromCurrency], $this->baseRates[$toCurrency])) {
            return null;
        }
        // Interpret table as relative to USD then derive cross rate.
        return $this->baseRates[$toCurrency] / $this->baseRates[$fromCurrency];
    }

    public function getExchangeRates(string $baseCurrency = 'USD'): array
    {
        $baseCurrency = strtoupper($baseCurrency);
        if (!isset($this->baseRates[$baseCurrency])) {
            $baseCurrency = 'USD';
        }
        $rates = [];
        foreach ($this->baseRates as $code => $rate) {
            $rates[$code] = $rate / $this->baseRates[$baseCurrency];
        }
        return $rates;
    }

    public function convert(float $amount, string $fromCurrency, string $toCurrency): ?float
    {
        $rate = $this->getExchangeRate($fromCurrency, $toCurrency);
        return $rate === null ? null : round($amount * $rate, 2);
    }
}
