<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CurrencyService;
use Illuminate\Http\JsonResponse;

class CurrencyController extends Controller
{
    protected CurrencyService $currencyService;

    /**
     * Controller constructor
     */
    public function __construct(CurrencyService $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    /**
     * Get list of commonly used currencies
     * 
     * @return JsonResponse
     */
    public function getCommonCurrencies(): JsonResponse
    {
        return response()->json($this->currencyService->getCommonCurrencies());
    }

    /**
     * Get complete list of available currencies
     * 
     * @return JsonResponse
     */
    public function getAllCurrencies(): JsonResponse
    {
        return response()->json($this->currencyService->getAllCurrencies());
    }
}