<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Contracts\DashboardServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Dashboard service instance
     *
     * @var DashboardServiceInterface
     */
    protected $dashboardService;

    /**
     * Constructor
     *
     * @param DashboardServiceInterface $dashboardService
     */
    public function __construct(DashboardServiceInterface $dashboardService)
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
            'invoiceCount' => $dashboardData['statistics']['invoice_count'],
            'clientCount' => $dashboardData['statistics']['client_count'],
            'suppliersCount' => $dashboardData['statistics']['suppliers_count'],
            'totalAmount' => $dashboardData['statistics']['total_amount'],
            'monthlyStats' => $dashboardData['monthly_stats'],
            'clients' => $dashboardData['clients']
        ]);
    }
}
