<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CountryService;
use Illuminate\Http\JsonResponse;

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
        $countries = $this->countryService->getCountriesForSelect();
        
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
        $country = $this->countryService->getCountryByCode($code);
        
        if (!$country) {
            return response()->json(['error' => 'Country not found'], 404);
        }
        
        return response()->json($country);
    }
}