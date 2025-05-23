<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Client;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Show the application dashboard with user statistics
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = Auth::user();
        
        $invoiceCount = Invoice::where('user_id', $user->id)->count();
        $clientCount = Client::where('user_id', $user->id)->count();
        $suppliersCount = Supplier::where('user_id', $user->id)->count();
        $totalAmount = Invoice::where('user_id', $user->id)->sum('payment_amount');
            
        $clients = Client::where('user_id', $user->id)
            ->with(['invoices' => function($query) {
                $query->select('client_id', DB::raw('SUM(payment_amount) as total'))
                      ->groupBy('client_id');
            }])
            ->get();

        // Get monthly statistics for chart - database-agnostic version
        $monthlyStats = DB::table('invoices')
            ->join('clients', 'invoices.client_id', '=', 'clients.id')
            ->where('clients.user_id', $user->id)
            ->whereNotNull('invoices.issue_date')
            ->where('invoices.issue_date', '>=', now()->subMonths(6))
            ->select(
                DB::raw('DATE_FORMAT(invoices.issue_date, "%Y-%m") as month'),
                DB::raw('SUM(invoices.payment_amount) as total')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return view('frontend.dashboard', compact(
            'invoiceCount',
            'clientCount',
            'suppliersCount',
            'totalAmount',
            'monthlyStats',
            'clients'
        ));
    }
}
