<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AresLookupController extends Controller
{
    /**
     * Lookup company information using ICO in ARES API
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function lookup(Request $request)
    {
        $ico = $request->query('ico');
        
        if (!$ico || strlen($ico) !== 8 || !ctype_digit($ico)) {
            return response()->json([
                'success' => false,
                'message' => __('ares.errors.invalid_ico')
            ]);
        }
        
        try {
            // Call ARES API
            $response = Http::get("https://ares.gov.cz/ekonomicke-subjekty-v-be/rest/ekonomicke-subjekty-res/{$ico}");
            
            // Parse JSON response
            $data = $response->json();

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => $data['popis'] ?? __('ares.errors.api_error')
                ]);
            }
            
            if (!$data || !isset($data['zaznamy']) || empty($data['zaznamy'])) {
                return response()->json([
                    'success' => false,
                    'message' => $data['popis'] ?? __('ares.errors.not_found')
                ]);
            }
            
            // Get the first record (should be the primary one)
            $record = $data['zaznamy'][0];
            
            // Extract company data
            $companyName = $record['obchodniJmeno'] ?? '';
            
            // Extract address from JSON
            $sidlo = $record['sidlo'] ?? [];
            $street = $sidlo['nazevUlice'] ?? '';
            $houseNumber = $sidlo['cisloDomovni'] ?? '';
            $city = $sidlo['nazevObce'] ?? '';
            $district = $sidlo['nazevCastiObce'] ?? '';
            $zip = $sidlo['psc'] ?? '';
            $country = $sidlo['kodStatu'] ?? '';
            
            // Combine street with house number
            if ($street && $houseNumber) {
                $street .= " {$houseNumber}";
            } elseif (!$street && $houseNumber) {
                $street = $houseNumber;
            }
            
            // Format ZIP code (from 12345 to 123 45)
            if ($zip) {
                $zip = substr($zip, 0, 3) . ' ' . substr($zip, 3);
            }
            
            // DIČ is not directly available in the JSON response, use default format
            $dic = 'CZ' . $ico;
            
            return response()->json([
                'success' => true,
                'message' => __('ares.success'),
                'data' => [
                    'ico' => $ico,
                    'name' => $companyName,
                    'street' => trim($street),
                    'city' => $city !== $district && $district ? "{$city}, {$district}" : $city,
                    'zip' => $zip,
                    'dic' => $dic,
                    'country' => $country
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('ARES lookup error: ' . $e->getMessage(), [
                'ico' => $ico,
                'exception' => $e
            ]);
            
            return response()->json([
                'success' => false,
                'message' => __('ares.errors.general_error')
            ]);
        }
    }
}
