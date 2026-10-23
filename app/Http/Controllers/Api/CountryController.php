<?php

namespace App\Http\Controllers\Api;

use App\Application\Shared\Geography\Contracts\CountryApplicationServiceInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class CountryController extends Controller
{
    protected CountryApplicationServiceInterface $countryService;

    public function __construct(CountryApplicationServiceInterface $countryService)
    {
        $this->countryService = $countryService;
    }

    /**
     * Get list of countries for select element
     */
    public function getCountries(): JsonResponse
    {
        $countries = $this->countryService->getCountries();

        // If no countries returned, use the fallback directly
        if (empty($countries)) {
            $fallback = [
                'CZ' => [
                    'code' => 'CZ',
                    'name' => 'Czech Republic',
                    'flag' => '🇨🇿',
                ],
                'SK' => [
                    'code' => 'SK',
                    'name' => 'Slovakia',
                    'flag' => '🇸🇰',
                ],
                'AT' => [
                    'code' => 'AT',
                    'name' => 'Austria',
                    'flag' => '🇦🇹',
                ],
                'DE' => [
                    'code' => 'DE',
                    'name' => 'Germany',
                    'flag' => '🇩🇪',
                ],
                'PL' => [
                    'code' => 'PL',
                    'name' => 'Poland',
                    'flag' => '🇵🇱',
                ],
                'GB' => [
                    'code' => 'GB',
                    'name' => 'United Kingdom',
                    'flag' => '🇬🇧',
                ],
                'US' => [
                    'code' => 'US',
                    'name' => 'United States',
                    'flag' => '🇺🇸',
                ],
            ];

            return response()->json($fallback);
        }

        return response()->json($countries);
    }

    /**
     * Get country details by code
     */
    public function getCountry(string $code): JsonResponse
    {
        $country = $this->countryService->getCountry($code);

        if (! $country) {
            abort(404, 'Country not found');
        }

        return response()->json($country);
    }
}
