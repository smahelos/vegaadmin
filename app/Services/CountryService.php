<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CountryService
{
    protected string $apiUrl = 'https://restcountries.com/v3.1';
    
    /**
     * Get list of all countries
     * 
     * @return array
     */
    public function getAllCountries(): array
    {
        return Cache::remember('countries_all', 86400, function () {
            try {
                $response = Http::get("{$this->apiUrl}/all?fields=name,cca2,flag");
                
                if ($response->successful()) {
                    $countries = $response->json();
                    
                    // Sort countries by name
                    usort($countries, function ($a, $b) {
                        return $a['name']['common'] <=> $b['name']['common'];
                    });
                    
                    return $countries;
                }
            } catch (\Exception $e) {
                Log::error('Error loading countries. API not responding: ' . $e->getMessage());
                
                return $this->getFallbackCountryCodes();
            }
        });
    }
    
    /**
     * Fallback data in case external API fails
     *
     * @return array
     */
    private function getFallbackCountryCodes(): array
    {
        return [
            'CZ' => 'CZ',
            'SK' => 'SK',
            'AT' => 'AT',
            'DE' => 'DE',
            'PL' => 'PL',
            'GB' => 'GB',
            'US' => 'US',
        ];
    }
    
    /**
     * Get countries in format suitable for select box with additional data
     * 
     * @return array
     */
    public function getCountriesForSelect(): array
    {
        $countries = $this->getAllCountries();
        $result = [];
        
        foreach ($countries as $country) {
            $result[$country['cca2']] = [
                'code' => $country['cca2'],
                'name' => $country['name']['common'],
                'flag' => $country['flag']
            ];
        }
        
        return $result;
    }
    
    /**
     * Get countries in simple code => name format for select box
     * 
     * @return array
     */
    public function getSimpleCountriesForSelect(): array
    {
        $countries = $this->getAllCountries();
        $result = [];
        
        foreach ($countries as $country) {
            if (isset($country['cca2'])) {
                $result[$country['cca2']] = $country['name']['common'];
            } else {
                return $countries;
            }
        }
        
        return $result;
    }

    /**
     * Get country codes for select element
     *
     * @return array
     */
    public function getCountryCodesForSelect(): array
    {
        $countries = $this->getAllCountries();
        
        // Sort by country name
        asort($countries);
        
        return $countries;
    }

    /**
     * Get details of specific country by code
     * 
     * @param string $code
     * @return array|null
     */
    public function getCountryByCode(string $code): ?array
    {
        $code = strtoupper($code);
        
        return Cache::remember("country_{$code}", 86400, function () use ($code) {
            try {
                $response = Http::get("{$this->apiUrl}/alpha/{$code}");
                
                if ($response->successful()) {
                    return $response->json()[0] ?? null;
                }
                
                return null;
            } catch (\Exception $e) {
                Log::error("Error fetching country data for code {$code}: " . $e->getMessage());
                return null;
            }
        });
    }
}