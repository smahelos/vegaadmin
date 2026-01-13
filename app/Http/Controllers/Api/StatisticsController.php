<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Application\Analytics\Contracts\ApiStatisticsApplicationServiceInterface;
use Illuminate\Http\Request;

class StatisticsController extends Controller
{
    public function __construct(private ApiStatisticsApplicationServiceInterface $statsApp) {}

    public function monthlyRevenue(Request $request)
    {
        return response()->json(['data' => $this->statsApp->monthlyRevenue($request)]);
    }

    public function clientRevenue(Request $request)
    {
        // Returns array of client_id, client_name, total
        return response()->json($this->statsApp->clientRevenue($request));
    }

    public function invoiceStatus(Request $request)
    {
        return response()->json(['data' => $this->statsApp->invoiceStatus($request)]);
    }

    public function paymentMethods(Request $request)
    {
        return response()->json(['data' => $this->statsApp->paymentMethods($request)]);
    }

    public function revenueExpenses(Request $request)
    {
        return response()->json(['data' => $this->statsApp->revenueExpenses($request)]);
    }
}
