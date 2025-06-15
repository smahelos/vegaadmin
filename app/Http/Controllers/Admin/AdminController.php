<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\AdminRequest;
use Backpack\CRUD\app\Http\Controllers\AdminController as BackpackAdminController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Invoice;
use App\Models\Client;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\User;
use App\Models\UserActivitySummary;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

class AdminController extends BackpackAdminController
{
    protected function setupDashboardRoutes($segment, $routeName, $controller)
    {
        Route::get($segment, $controller.'@dashboard')->name($routeName);
    }

    public function dashboard()
    {
        $this->data['title'] = trans('backpack::base.dashboard');
        $this->data['breadcrumbs'] = [
            trans('backpack::crud.admin') => backpack_url('dashboard'),
            trans('backpack::base.dashboard') => false,
        ];

        // Get basic statistics
        $dashboardStats = $this->getDashboardStats();
        
        // Get user activity statistics
        $userActivityStats = $this->getUserActivityStats();

        return view(backpack_view('dashboard'), compact('dashboardStats', 'userActivityStats'));
    }

    /**
     * Get basic dashboard statistics
     */
    private function getDashboardStats()
    {
        try {
            $totalInvoices = Invoice::count();
            $totalClients = Client::count();
            $totalSuppliers = Supplier::count();
            $totalProducts = Product::count();
            $totalUsers = User::count();

            // Recent activity (last 30 days)
            $recentInvoices = Invoice::where('created_at', '>=', now()->subDays(30))->count();
            $recentClients = Client::where('created_at', '>=', now()->subDays(30))->count();

            // Revenue statistics
            $totalRevenue = Invoice::sum('payment_amount');
            $monthlyRevenue = Invoice::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('payment_amount');

            return [
                'total_invoices' => $totalInvoices,
                'total_clients' => $totalClients,
                'total_suppliers' => $totalSuppliers,
                'total_products' => $totalProducts,
                'total_users' => $totalUsers,
                'recent_invoices' => $recentInvoices,
                'recent_clients' => $recentClients,
                'total_revenue' => $totalRevenue,
                'monthly_revenue' => $monthlyRevenue
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get dashboard stats: ' . $e->getMessage());
            return [
                'total_invoices' => 0,
                'total_clients' => 0,
                'total_suppliers' => 0,
                'total_products' => 0,
                'total_users' => 0,
                'recent_invoices' => 0,
                'recent_clients' => 0,
                'total_revenue' => 0,
                'monthly_revenue' => 0
            ];
        }
    }

    /**
     * Get user activity statistics using the user_activity_summary view
     * Moved from DatabaseDashboardController
     */
    private function getUserActivityStats()
    {
        try {
            $totalUsers = UserActivitySummary::count();
            $activeUsers = UserActivitySummary::where('invoices_last_30_days', '>', 0)->count();
            $highActivityUsers = UserActivitySummary::where('invoices_last_30_days', '>=', 20)->count();
            $inactiveUsers = UserActivitySummary::where('invoices_last_30_days', 0)->count();

            // Get most active users in last 30 days
            $mostActiveUsers = UserActivitySummary::where('invoices_last_30_days', '>', 0)
                ->orderBy('invoices_last_30_days', 'desc')
                ->limit(5)
                ->get();

            // Calculate activity distribution
            $activityDistribution = [
                'high' => UserActivitySummary::where('invoices_last_30_days', '>=', 20)->count(),
                'medium' => UserActivitySummary::whereBetween('invoices_last_30_days', [5, 19])->count(),
                'low' => UserActivitySummary::whereBetween('invoices_last_30_days', [1, 4])->count(),
                'inactive' => UserActivitySummary::where('invoices_last_30_days', 0)->count()
            ];

            return [
                'total_users' => $totalUsers,
                'active_users' => $activeUsers,
                'high_activity_users' => $highActivityUsers,
                'inactive_users' => $inactiveUsers,
                'activity_rate' => $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100, 1) : 0,
                'most_active_users' => $mostActiveUsers,
                'activity_distribution' => $activityDistribution
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get user activity stats: ' . $e->getMessage());
            return [
                'error' => 'Unable to fetch user activity statistics: ' . $e->getMessage(),
                'total_users' => 0,
                'active_users' => 0,
                'high_activity_users' => 0,
                'inactive_users' => 0,
                'activity_rate' => 0,
                'most_active_users' => collect([]),
                'activity_distribution' => []
            ];
        }
    }
}
