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
                Log::info('Fetching countries from API', ['url' => "{$this->apiUrl}/all?fields=name,cca2,flag"]);
                
                $response = Http::timeout(5)->get("{$this->apiUrl}/all?fields=name,cca2,flag");
                
                if ($response->successful()) {
                    $countries = $response->json();
                    
                    // Log API response
                    Log::info('API returned countries', [
                        'count' => count($countries),
                        'first_few' => array_slice($countries, 0, 3)
                    ]);
                    
                    // Check if we got valid data
                    if (empty($countries) || !is_array($countries)) {
                        Log::warning('API returned empty or invalid data', ['response' => $response->body()]);
                        return $this->getFallbackCountryCodes();
                    }
                    
                    // Sort countries by name
                    usort($countries, function ($a, $b) {
                        return $a['name']['common'] <=> $b['name']['common'];
                    });
                    
                    return $countries;
                }
                
                // Log failed API response
                Log::warning('API request was not successful', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                
                // Return fallback data if API call wasn't successful
                return $this->getFallbackCountryCodes();
            } catch (\Exception $e) {
                Log::error('Error loading countries. API not responding: ' . $e->getMessage(), [
                    'exception' => get_class($e),
                    'trace' => $e->getTraceAsString()
                ]);
                
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
            Log::warning('No countries returned from getAllCountries()');
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
                Log::warning('Country with missing data', ['country' => $country]);
            }
        }
        
        if (empty($result)) {
            Log::warning('No valid countries found after processing');
            return $this->getFallbackCountryCodesFormatted();
        }
        
        Log::info('Returning countries for select', ['count' => count($result)]);
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
        
        Log::info('Using formatted fallback country data', ['count' => count($fallback)]);
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
                Log::error("Error fetching country data for code {$code}: " . $e->getMessage());
                return null;
            }
        });
    }
}
