<?php

namespace App\Domain\Shared\Money\Contracts;

/**
 * Currency exchange service contract.
 * Provides methods to retrieve FX rates and convert amounts between currencies.
 */
interface CurrencyExchangeServiceInterface
{
    /**
     * Get exchange rate from one currency to another.
     * If source and target are the same returns 1.0.
     * Returns null when rate cannot be determined.
     *
     * @param string $fromCurrency ISO 4217 source currency code (case insensitive)
     * @param string $toCurrency ISO 4217 target currency code (case insensitive)
     * @return float|null Exchange rate or null if unavailable
     */
    public function getExchangeRate(string $fromCurrency, string $toCurrency): ?float;

    /**
     * Get all exchange rates for a base currency.
     * Invalid base codes should fallback internally to a default (USD).
     *
     * @param string $baseCurrency ISO 4217 base currency code (default USD)
     * @return array<string,float> Associative array: currency code => rate
     */
    public function getExchangeRates(string $baseCurrency = 'USD'): array;

    /**
     * Convert monetary amount between currencies.
     * Returns null if conversion is not possible.
     *
     * @param float $amount Positive numeric amount to convert
     * @param string $fromCurrency ISO 4217 source currency code
     * @param string $toCurrency ISO 4217 target currency code
     * @return float|null Converted amount rounded to 2 decimals or null
     */
    public function convert(float $amount, string $fromCurrency, string $toCurrency): ?float;
}
