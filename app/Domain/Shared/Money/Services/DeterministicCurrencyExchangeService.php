<?php

namespace App\Domain\Shared\Money\Services;

use App\Domain\Shared\Money\Contracts\CurrencyExchangeServiceInterface;

/**
 * Deterministic currency exchange service used for tests and repeatable scenarios.
 *
 * Provides a fixed set of base rates (interpreted as relative to a canonical base currency, default USD)
 * and derives cross rates arithmetically so that conversions are fully stable and do not perform
 * external HTTP calls, retries or circuit breaker logic.
 */
class DeterministicCurrencyExchangeService implements CurrencyExchangeServiceInterface
{
    /**
     * Map of currency code => relative rate to canonical base (e.g. USD = 1.0).
     * @var array<string,float>
     */
    private array $baseRates;

    /** Canonical base currency all rates relate to. */
    private string $canonicalBase;

    /**
     * @param array<string,float>|null $baseRates Optional rate table (relative to canonical base)
     * @param string $canonicalBase Canonical base currency code (default USD)
     */
    public function __construct(?array $baseRates = null, string $canonicalBase = 'USD')
    {
        $this->canonicalBase = strtoupper($canonicalBase);
        $this->baseRates = $this->sanitizeRates($baseRates ?? [
            'USD' => 1.0,
            'EUR' => 0.8,
            'CZK' => 20.0,
            'GBP' => 0.7,
            'PLN' => 3.9,
        ]);
        if (!isset($this->baseRates[$this->canonicalBase])) {
            // Ensure canonical base always present as 1.0 reference
            $this->baseRates[$this->canonicalBase] = 1.0;
        }
    }

    /** @inheritDoc */
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
        // Cross rate derived through canonical base.
        return $this->baseRates[$toCurrency] / $this->baseRates[$fromCurrency];
    }

    /** @inheritDoc */
    public function getExchangeRates(string $baseCurrency = 'USD'): array
    {
        $baseCurrency = strtoupper($baseCurrency);
        if (!isset($this->baseRates[$baseCurrency])) {
            $baseCurrency = $this->canonicalBase;
        }
        $rates = [];
        $baseRate = $this->baseRates[$baseCurrency];
        foreach ($this->baseRates as $code => $rate) {
            $rates[$code] = $rate / $baseRate; // normalize relative to requested base
        }
        return $rates;
    }

    /** @inheritDoc */
    public function convert(float $amount, string $fromCurrency, string $toCurrency): ?float
    {
        $rate = $this->getExchangeRate($fromCurrency, $toCurrency);
        return $rate === null ? null : round($amount * $rate, 2);
    }

    /**
     * Sanitize incoming custom rates (uppercase codes and positive numeric values).
     * Invalid (<=0) rates are discarded to avoid division anomalies.
     *
     * @param array<string,float> $rates
     * @return array<string,float>
     */
    private function sanitizeRates(array $rates): array
    {
        $clean = [];
        foreach ($rates as $code => $value) {
            $uc = strtoupper($code);
            if (!preg_match('/^[A-Z]{3}$/', $uc)) { continue; }
            if (!is_numeric($value) || $value <= 0) { continue; }
            $clean[$uc] = (float)$value;
        }
        return $clean;
    }
}
