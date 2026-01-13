<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Application\Shared\Money\Contracts\CurrencyApplicationServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CurrencyController extends Controller
{
    protected CurrencyApplicationServiceInterface $currencyAppService;

    /**
     * Controller constructor
     */
    public function __construct(CurrencyApplicationServiceInterface $currencyAppService)
    {
        $this->currencyAppService = $currencyAppService;
    }

    /**
     * Get list of commonly used currencies
     *
     * @return JsonResponse
     */
    public function getCommonCurrencies(): JsonResponse
    {
    return response()->json($this->currencyAppService->getCommonCurrencies());
    }

    /**
     * Get complete list of available currencies
     *
     * @return JsonResponse
     */
    public function getAllCurrencies(): JsonResponse
    {
    return response()->json($this->currencyAppService->getAllCurrencies());
    }

    /**
     * Get exchange rate between two currencies
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getExchangeRate(Request $request): JsonResponse
    {
        $request->validate([
            'from' => 'required|string|size:3',
            'to' => 'required|string|size:3'
        ]);

        $from = strtoupper($request->input('from'));
        $to = strtoupper($request->input('to'));

        $rate = $this->currencyAppService->getExchangeRate($from, $to);

        if ($rate === null) {
            return response()->json(['error' => __('Currency exchange rate not available')], 404);
        }

        return response()->json([
            'from' => $from,
            'to' => $to,
            'rate' => $rate
        ]);
    }

    /**
     * Convert amount from one currency to another
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function convertCurrency(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric',
            'from' => 'required|string|size:3',
            'to' => 'required|string|size:3'
        ]);

        $amount = (float) $request->input('amount');
        $from = strtoupper($request->input('from'));
        $to = strtoupper($request->input('to'));

        $convertedAmount = $this->currencyAppService->convert($amount, $from, $to);

        if ($convertedAmount === null) {
            return response()->json(['error' => __('Currency conversion not available')], 404);
        }

        return response()->json([
            'original' => [
                'amount' => $amount,
                'currency' => $from
            ],
            'converted' => [
                'amount' => $convertedAmount,
                'currency' => $to
            ],
            'rate' => $this->currencyAppService->getExchangeRate($from, $to)
        ]);
    }
}
