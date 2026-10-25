<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Application\Analytics\Contracts\ApiStatisticsApplicationServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\StatisticsRequest;
use Illuminate\Http\JsonResponse;

final class StatisticsController extends Controller
{
    public function __construct(
        private readonly ApiStatisticsApplicationServiceInterface $statistics,
    ) {}

    public function monthlyRevenue(StatisticsRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->statistics->monthlyRevenue($request->userId(), $request->filter())]);
    }

    public function clientRevenue(StatisticsRequest $request): JsonResponse
    {
        return response()->json($this->statistics->clientRevenue($request->userId(), $request->filter()));
    }

    public function invoiceStatus(StatisticsRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->statistics->invoiceStatus($request->userId(), $request->filter())]);
    }

    public function paymentMethods(StatisticsRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->statistics->paymentMethods($request->userId(), $request->filter())]);
    }

    public function revenueExpenses(StatisticsRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->statistics->revenueExpenses($request->userId(), $request->filter())]);
    }
}
