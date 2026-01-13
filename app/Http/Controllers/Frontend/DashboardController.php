<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Application\Analytics\Contracts\DashboardApplicationServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Dashboard service instance
     *
     * @var DashboardApplicationServiceInterface
     */
    protected $dashboardService;

    /**
     * Constructor
     *
     * @param DashboardApplicationServiceInterface $dashboardService
     */
    public function __construct(DashboardApplicationServiceInterface $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Show the application dashboard with user statistics
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = Auth::user();
        $dashboardData = $this->dashboardService->getDashboardData($user);

        return view('frontend.dashboard', [
            'invoiceCount' => $dashboardData['statistics_formatted']['invoice_count'],
            'clientCount' => $dashboardData['statistics_formatted']['client_count'],
            'suppliersCount' => $dashboardData['statistics_formatted']['suppliers_count'],
            'totalAmount' => $dashboardData['statistics_formatted']['total_amount'],
            'monthlyStats' => $dashboardData['monthly_stats'],
            'clients' => $dashboardData['clients']
        ]);
    }
}
