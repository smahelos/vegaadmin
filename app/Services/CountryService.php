<?php

namespace App\Services;

use App\Contracts\CountryServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class CountryService implements CountryServiceInterface
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
                $response = Http::timeout(5)->get("{$this->apiUrl}/all?fields=name,cca2,flag");
                
                if ($response->successful()) {
                    $countries = $response->json();
                    
                    // Check if we got valid data
                    if (empty($countries) || !is_array($countries)) {
                        return $this->getFallbackCountryCodes();
                    }
                    
                    // Sort countries by name
                    usort($countries, function ($a, $b) {
                        return $a['name']['common'] <=> $b['name']['common'];
                    });
                    
                    return $countries;
                }
                
                // Return fallback data if API call wasn't successful
                return $this->getFallbackCountryCodes();
            } catch (\Exception $e) {
                // API not responding, return fallback data
                return $this->getFallbackCountryCodes();
            }
        });
    }
    
    /**
     * Fallback data in case external API fails
     * Format consistent with API response
     *
     * @return array
     */
    private function getFallbackCountryCodes(): array
    {
        return [
            [
                'cca2' => 'CZ',
                'name' => ['common' => 'Czech Republic'],
                'flag' => '🇨🇿'
            ],
            [
                'cca2' => 'SK',
                'name' => ['common' => 'Slovakia'],
                'flag' => '🇸🇰'
            ],
            [
                'cca2' => 'AT',
                'name' => ['common' => 'Austria'],
                'flag' => '🇦🇹'
            ],
            [
                'cca2' => 'DE',
                'name' => ['common' => 'Germany'],
                'flag' => '🇩🇪'
            ],
            [
                'cca2' => 'PL',
                'name' => ['common' => 'Poland'],
                'flag' => '🇵🇱'
            ],
            [
                'cca2' => 'GB',
                'name' => ['common' => 'United Kingdom'],
                'flag' => '🇬🇧'
            ],
            [
                'cca2' => 'US',
                'name' => ['common' => 'United States'],
                'flag' => '🇺🇸'
            ],
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
        
        if (empty($countries)) {
            return $this->getFallbackCountryCodesFormatted();
        }
        
        foreach ($countries as $country) {
            if (isset($country['cca2']) && isset($country['name']['common']) && isset($country['flag'])) {
                $result[$country['cca2']] = [
                    'code' => $country['cca2'],
                    'name' => $country['name']['common'],
                    'flag' => $country['flag']
                ];
            } else {
                // Country with missing data - skip it
            }
        }
        
        if (empty($result)) {
            return $this->getFallbackCountryCodesFormatted();
        }
        
        return $result;
    }
    
    /**
     * Get fallback country codes formatted for select
     * 
     * @return array
     */
    private function getFallbackCountryCodesFormatted(): array
    {
        $fallback = [
            'CZ' => ['code' => 'CZ', 'name' => 'Czech Republic', 'flag' => '🇨🇿'],
            'SK' => ['code' => 'SK', 'name' => 'Slovakia', 'flag' => '🇸🇰'],
            'AT' => ['code' => 'AT', 'name' => 'Austria', 'flag' => '🇦🇹'],
            'DE' => ['code' => 'DE', 'name' => 'Germany', 'flag' => '🇩🇪'],
            'PL' => ['code' => 'PL', 'name' => 'Poland', 'flag' => '🇵🇱'],
            'GB' => ['code' => 'GB', 'name' => 'United Kingdom', 'flag' => '🇬🇧'],
            'US' => ['code' => 'US', 'name' => 'United States', 'flag' => '🇺🇸'],
        ];
        
        return $fallback;
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
            if (isset($country['cca2']) && isset($country['name']['common'])) {
                $result[$country['cca2']] = $country['name']['common'];
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
        $result = [];
        
        foreach ($countries as $country) {
            if (isset($country['cca2']) && isset($country['name']['common'])) {
                $result[$country['cca2']] = $country['name']['common'];
            }
        }
        
        // Sort by country name
        asort($result);
        
        return $result;
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
                return null;
            }
        });
    }
}
