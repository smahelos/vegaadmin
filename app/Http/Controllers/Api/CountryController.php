<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CountryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class CountryController extends Controller
{
    protected CountryService $countryService;

    public function __construct(CountryService $countryService)
    {
        $this->countryService = $countryService;
    }

    /**
     * Get list of countries for select element
     * 
     * @return JsonResponse
     */
    public function getCountries(): JsonResponse
    {
        // Clear the cache to ensure fresh data
        Cache::forget('countries_all');
        
        $countries = $this->countryService->getCountriesForSelect();
        
        // Log the result for debugging
        Log::info('Countries API response', ['count' => count($countries), 'sample' => array_slice($countries, 0, 3)]);
        
        // If no countries returned, use the fallback directly
        if (empty($countries)) {
            $fallback = [
                'CZ' => ['code' => 'CZ', 'name' => 'Czech Republic', 'flag' => '🇨🇿'],
                'SK' => ['code' => 'SK', 'name' => 'Slovakia', 'flag' => '🇸🇰'],
                'AT' => ['code' => 'AT', 'name' => 'Austria', 'flag' => '🇦🇹'],
                'DE' => ['code' => 'DE', 'name' => 'Germany', 'flag' => '🇩🇪'],
                'PL' => ['code' => 'PL', 'name' => 'Poland', 'flag' => '🇵🇱'],
                'GB' => ['code' => 'GB', 'name' => 'United Kingdom', 'flag' => '🇬🇧'],
                'US' => ['code' => 'US', 'name' => 'United States', 'flag' => '🇺🇸'],
            ];
            Log::warning('Using controller fallback data for countries');
            return response()->json($fallback);
        }
        
        return response()->json($countries);
    }

    /**
     * Get country details by code
     * 
     * @param string $code
     * @return JsonResponse
     */
    public function getCountry(string $code): JsonResponse
    {
        // Clear specific country cache
        Cache::forget("country_{$code}");
        
        $country = $this->countryService->getCountryByCode($code);
        
        if (!$country) {
            return response()->json(['error' => 'Country not found'], 404);
        }
        
        return response()->json($country);
    }
}
