<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Application\Shared\Geography\Services\CountryApplicationService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class CountryController extends Controller
{
    public function __construct(
        private readonly CountryApplicationService $countryService,
    ) {}

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

            $responseFactory = response();

            return $responseFactory->json($fallback);
        }

        $responseFactory = response();

        return $responseFactory->json($countries);
    }

    /**
     * Get country details by code
     */
    public function getCountry(string $code): JsonResponse
    {
        $country = $this->countryService->getCountry($code);

        if (!$country) {
            abort(404, 'Country not found');
        }

        $responseFactory = response();

        return $responseFactory->json($country);
    }
}
