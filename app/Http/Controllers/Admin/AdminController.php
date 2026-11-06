<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Business;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserActivitySummary;
use Backpack\CRUD\app\Http\Controllers\AdminController as BackpackAdminController;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

final class AdminController extends BackpackAdminController
{
    public function dashboard(): View
    {
        $this->data['title'] = __('backpack::base.dashboard');
        $this->data['breadcrumbs'] = [
            __('backpack::crud.admin') => backpack_url('dashboard'),
            __('backpack::base.dashboard') => false,
        ];

        // Get basic statistics
        $dashboardStats = $this->getDashboardStats();

        // Get user activity statistics
        $userActivityStats = $this->getUserActivityStats();

        return view(
            backpack_view('dashboard'),
            compact('dashboardStats', 'userActivityStats'),
        );
    }

    /**
     * Get basic dashboard statistics
     */
    private function getDashboardStats(): array
    {
        try {
            $totalInvoices = Invoice::count();
            $query = Contact::whereNull('archived_at');
            $totalContacts = $query->count();
            $query = Business::whereNull('archived_at');
            $totalBusinesses = $query->count();
            $totalProducts = Product::count();
            $totalUsers = User::count();

            // Recent activity (last 30 days)
            $now = CarbonImmutable::now();
            $query = Invoice::where(
                'created_at',
                '>=',
                $now->subDays(30),
            );
            $recentInvoices = $query->count();
            $query = Contact::whereNull('archived_at');
            $query->where(
                'created_at',
                '>=',
                $now->subDays(30),
            );
            $recentContacts = $query->count();

            // Revenue statistics
            $totalRevenue = Invoice::sum('payment_amount');
            $query = Invoice::whereMonth('created_at', $now->month);
            $query->whereYear('created_at', $now->year);
            $monthlyRevenue = $query->sum('payment_amount');

            // Subscriptions statistics
            $query = Subscription::where(
                'status',
                '=',
                'active',
            );
            $subscriptions = $query->count();

            return [
                'total_invoices' => $totalInvoices,
                'total_contacts' => $totalContacts,
                'total_businesses' => $totalBusinesses,
                'total_products' => $totalProducts,
                'total_users' => $totalUsers,
                'recent_invoices' => $recentInvoices,
                'recent_contacts' => $recentContacts,
                'total_revenue' => $totalRevenue,
                'monthly_revenue' => $monthlyRevenue,
                'total_subscriptions' => $subscriptions,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get dashboard stats: ' . $e->getMessage());

            return [
                'total_invoices' => 0,
                'total_contacts' => 0,
                'total_businesses' => 0,
                'total_products' => 0,
                'total_users' => 0,
                'recent_invoices' => 0,
                'recent_contacts' => 0,
                'total_revenue' => 0,
                'monthly_revenue' => 0,
                'total_subscriptions' => 0,
            ];
        }
    }

    /**
     * Get user activity statistics using the user_activity_summary view
     * Moved from DatabaseDashboardController
     */
    private function getUserActivityStats(): array
    {
        try {
            $totalUsers = UserActivitySummary::count();
            $query = UserActivitySummary::where(
                'invoices_last_30_days',
                '>',
                0,
            );
            $activeUsers = $query->count();
            $query = UserActivitySummary::where(
                'invoices_last_30_days',
                '>=',
                20,
            );
            $highActivityUsers = $query->count();
            $query = UserActivitySummary::where('invoices_last_30_days', 0);
            $inactiveUsers = $query->count();

            // Get most active users in last 30 days
            $query = UserActivitySummary::where(
                'invoices_last_30_days',
                '>',
                0,
            );
            $query->orderBy('invoices_last_30_days', 'desc');
            $query->limit(5);
            $mostActiveUsers = $query->get();

            // Calculate activity distribution
            $highQuery = UserActivitySummary::where(
                'invoices_last_30_days',
                '>=',
                20,
            );
            $mediumQuery = UserActivitySummary::whereBetween('invoices_last_30_days', [5, 19]);
            $lowQuery = UserActivitySummary::whereBetween('invoices_last_30_days', [1, 4]);
            $inactiveQuery = UserActivitySummary::where('invoices_last_30_days', 0);
            $activityDistribution = [
                'high' => $highQuery->count(),
                'medium' => $mediumQuery->count(),
                'low' => $lowQuery->count(),
                'inactive' => $inactiveQuery->count(),
            ];

            return [
                'total_users' => $totalUsers,
                'active_users' => $activeUsers,
                'high_activity_users' => $highActivityUsers,
                'inactive_users' => $inactiveUsers,
                'activity_rate' => $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100, 1) : 0,
                'most_active_users' => $mostActiveUsers,
                'activity_distribution' => $activityDistribution,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get user activity stats', ['exception' => $e]);

            return [
                'total_users' => 0,
                'active_users' => 0,
                'high_activity_users' => 0,
                'inactive_users' => 0,
                'activity_rate' => 0,
                'most_active_users' => collect([]),
                'activity_distribution' => [],
            ];
        }
    }
}
