<?php

namespace App\Http\Controllers\Frontend;

use App\Application\Analytics\Contracts\DashboardApplicationServiceInterface;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Constructor
     */
    public function __construct(private readonly DashboardApplicationServiceInterface $dashboardService) {}

    /**
     * Show the application dashboard with user statistics
     */
    public function index(): View
    {
        $user = Auth::user();
        $dashboardData = $this->dashboardService->getDashboardData($user);

        return view('frontend.dashboard', [
            'invoiceCount' => $dashboardData['statistics_formatted']['invoice_count'],
            'clientCount' => $dashboardData['statistics_formatted']['client_count'],
            'suppliersCount' => $dashboardData['statistics_formatted']['suppliers_count'],
            'totalAmount' => $dashboardData['statistics_formatted']['total_amount'],
            'clients' => $dashboardData['clients'],
        ]);
    }
}
