<?php

namespace App\Contracts;

interface CurrencyExchangeServiceInterface
{
    /**
     * Get exchange rate from one currency to another
     * 
     * @param string $fromCurrency Source currency code
     * @param string $toCurrency Target currency code
     * @return float|null Exchange rate or null if not found
     */
    public function getExchangeRate(string $fromCurrency, string $toCurrency): ?float;

    /**
     * Get all exchange rates for a base currency
     * 
     * @param string $baseCurrency Base currency code (default: 'USD')
     * @return array Array with currency codes as keys and rates as values
     */
    public function getExchangeRates(string $baseCurrency = 'USD'): array;

    /**
     * Convert amount from one currency to another
     * 
     * @param float $amount Amount to convert
     * @param string $fromCurrency Source currency code
     * @param string $toCurrency Target currency code
     * @return float|null Converted amount or null if conversion failed
     */
    public function convert(float $amount, string $fromCurrency, string $toCurrency): ?float;
}
