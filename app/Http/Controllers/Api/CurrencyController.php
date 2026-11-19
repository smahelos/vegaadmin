<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Application\Shared\Money\Services\CurrencyApplicationService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CurrencyController extends Controller
{
    public function __construct(
        private readonly CurrencyApplicationService $currencyAppService,
    ) {}

    /**
     * Get exchange rate between two currencies
     */
    public function getExchangeRate(Request $request): JsonResponse
    {
        $request->validate(
            [
                'from' => 'required|string|size:3',
                'to' => 'required|string|size:3',
            ],
        );

        $from = strtoupper(
            $request->input('from'),
        );
        $to = strtoupper(
            $request->input('to'),
        );

        $rate = $this->currencyAppService->getExchangeRate($from, $to);

        if ($rate === null) {
            abort(404, 'Currency exchange rate not available');
        }

        $responseFactory = response();

        return $responseFactory->json([
            'from' => $from,
            'to' => $to,
            'rate' => $rate,
        ]);
    }

    /**
     * Convert amount from one currency to another
     */
    public function convertCurrency(Request $request): JsonResponse
    {
        $request->validate(
            [
                'amount' => 'required|numeric',
                'from' => 'required|string|size:3',
                'to' => 'required|string|size:3',
            ],
        );

        $amount = (float) $request->input('amount');
        $from = strtoupper(
            $request->input('from'),
        );
        $to = strtoupper(
            $request->input('to'),
        );

        $convertedAmount = $this->currencyAppService->convert(
            $amount,
            $from,
            $to,
        );

        if ($convertedAmount === null) {
            abort(404, 'Currency conversion not available');
        }

        $responseFactory = response();

        return $responseFactory->json(
            [
                'original' => [
                    'amount' => $amount,
                    'currency' => $from,
                ],
                'converted' => [
                    'amount' => $convertedAmount,
                    'currency' => $to,
                ],
                'rate' => $this->currencyAppService->getExchangeRate($from, $to),
            ],
        );
    }
}
