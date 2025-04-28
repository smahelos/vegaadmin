<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CurrencyService
{
    /**
     * API URL for getting available currencies
     */
    protected string $apiUrl = 'https://open.er-api.com/v6/latest';

    /**
     * Get list of all available currencies
     * 
     * @return array
     */
    public function getAllCurrencies(): array
    {
        return Cache::remember('currencies_all', 86400, function () {
            try {
                $response = Http::get($this->apiUrl);
                
                if ($response->successful()) {
                    $data = $response->json();
                    if (isset($data['rates'])) {
                        $currencies = [];
                        
                        foreach (array_keys($data['rates']) as $code) {
                            $currencies[$code] = $code;
                        }
                        
                        // Sort by currency code
                        ksort($currencies);
                        
                        return $currencies;
                    }
                }
                
                // Fallback if API fails
                return $this->getFallbackCurrencies();
            } catch (\Exception $e) {
                Log::error('Error fetching currency data: ' . $e->getMessage());
                return $this->getFallbackCurrencies();
            }
        });
    }

    /**
     * Get fallback currencies when API fails
     * 
     * @return array
     */
    private function getFallbackCurrencies(): array
    {
        return [
            'CZK' => 'CZK',
            'EUR' => 'EUR',
            'USD' => 'USD',
            'GBP' => 'GBP'
        ];
    }

    /**
     * Get list of commonly used currencies
     * 
     * @return array
     */
    public function getCommonCurrencies(): array
    {
        return Cache::remember('currencies_common', 86400, function () {
            $commonCodes = ['CZK', 'EUR', 'USD', 'GBP', 'PLN', 'HUF', 'CHF'];
            $allCurrencies = $this->getAllCurrencies();
            
            $result = [];
            foreach ($commonCodes as $code) {
                if (isset($allCurrencies[$code])) {
                    $result[$code] = $allCurrencies[$code];
                }
            }
            
            return $result;
        });
    }
}