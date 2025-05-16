<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CurrencyExchangeService
{
    /**
     * API URL for getting exchange rates
     */
    protected string $apiUrl = 'https://open.er-api.com/v6/latest';

    /**
     * Get exchange rate from one currency to another
     * 
     * @param string $fromCurrency
     * @param string $toCurrency
     * @return float|null
     */
    public function getExchangeRate(string $fromCurrency, string $toCurrency): ?float
    {
        if ($fromCurrency === $toCurrency) {
            return 1.0;
        }

        $rates = $this->getExchangeRates();
        
        if (isset($rates[$fromCurrency]) && isset($rates[$toCurrency])) {
            // Convert through base currency (usually USD)
            return $rates[$toCurrency] / $rates[$fromCurrency];
        }
        
        return null;
    }

    /**
     * Get all exchange rates
     * 
     * @param string $baseCurrency Base currency for rates (default: USD)
     * @return array
     */
    public function getExchangeRates(string $baseCurrency = 'USD'): array
    {
        return Cache::remember('exchange_rates_' . $baseCurrency, 3600, function () use ($baseCurrency) {
            try {
                $response = Http::get($this->apiUrl, [
                    'base' => $baseCurrency
                ]);
                
                if ($response->successful()) {
                    $data = $response->json();
                    if (isset($data['rates'])) {
                        return $data['rates'];
                    }
                }
                
                // Fallback if API fails
                return $this->getFallbackRates();
            } catch (\Exception $e) {
                Log::error('Error fetching currency exchange rates: ' . $e->getMessage());
                return $this->getFallbackRates();
            }
        });
    }

    /**
     * Get fallback exchange rates when API fails
     * 
     * @return array
     */
    private function getFallbackRates(): array
    {
        return [
            'USD' => 1.0,
            'EUR' => 0.85,
            'CZK' => 21.5,
            'GBP' => 0.73,
            'PLN' => 3.8,
            'HUF' => 300,
            'CHF' => 0.92
        ];
    }

    /**
     * Convert amount from one currency to another
     * 
     * @param float $amount
     * @param string $fromCurrency
     * @param string $toCurrency
     * @return float|null
     */
    public function convert(float $amount, string $fromCurrency, string $toCurrency): ?float
    {
        $rate = $this->getExchangeRate($fromCurrency, $toCurrency);
        
        if ($rate !== null) {
            return round($amount * $rate, 2);
        }
        
        return null;
    }
}
