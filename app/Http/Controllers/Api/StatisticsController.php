<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Client;
use App\Models\Supplier;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class StatisticsController extends Controller
{
    /**
     * Get monthly revenue statistics
     */
    public function monthlyRevenue(Request $request)
    {
        $query = Invoice::select(
            // Change to use issue_date instead of invoice_date
            DB::raw('DATE_FORMAT(STR_TO_DATE(issue_date, "%Y-%m-%d"), "%Y-%m") as month'),
            DB::raw('SUM(payment_amount) as total'),
            // Join with statuses table to check payment status
            DB::raw('SUM(CASE WHEN statuses.slug = "paid" THEN payment_amount ELSE 0 END) as paid')
        )
        ->leftJoin('statuses', 'invoices.payment_status_id', '=', 'statuses.id')
        ->groupBy('month')
        ->orderBy('month');
        
        $query = $this->applyFilters($query, $request);
        
        $data = $query->get();
        
        return response()->json([
            'data' => $data
        ]);
    }
    
    /**
     * Get revenue by client statistics
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function clientRevenue(Request $request)
    {
        Log::info('Client revenue request received', [
            'time_range' => $request->input('time_range', 'year'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to')
        ]);
        try {
            // Use Laravel Query Builder instead of DB facade for better readability and security
            $timeRange = $request->input('time_range', 'year');
            $startDate = $this->calculateStartDate($timeRange);

            // Use Invoice model which has defined relationships
            $query = Invoice::query()
                ->join('clients', 'invoices.client_id', '=', 'clients.id')
                ->select([
                    'clients.id as client_id',
                    'clients.name as client_name',
                    DB::raw('SUM(invoices.payment_amount) as total')
                ])
                ->where('invoices.user_id', Auth::id())
                ->whereDate(DB::raw("STR_TO_DATE(invoices.issue_date, '%Y-%m-%d')"), '>=', $startDate)
                ->groupBy(['clients.id', 'clients.name'])  // Použijeme pole pro lepší čitelnost
                ->orderByDesc('total')
                ->limit(10);
                
            Log::info('Client revenue query built', [
                'sql' => $query->toSql(),
                'bindings' => $query->getBindings()
            ]);

            $clients = $query->get();
            
            return response()->json($clients);
        } catch (\Exception $e) {
            \Log::error('Error in clientRevenue: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return response()->json([
                'error' => $e->getMessage(),
                'sql' => $query->toSql(),
                'bindings' => $query->getBindings()
            ], 500);
        }
    }

    /**
     * Calculate start date based on time range
     * 
     * @param string $timeRange
     * @return \Carbon\Carbon
     */
    private function calculateStartDate(string $timeRange): Carbon
    {
        $now = now();
        
        return match ($timeRange) {
            'month' => $now->subMonth(),
            'quarter' => $now->subMonths(3),
            'half_year', '6_months' => $now->subMonths(6),
            'year' => $now->subYear(),
            default => $now->subYear(),
        };
    }
    
    /**
     * Get invoice status statistics
     */
    public function invoiceStatus(Request $request)
    {
        $query = Invoice::select(
            'statuses.slug as status',
            DB::raw('COUNT(*) as count')
        )
        ->where('user_id', Auth::id())
        ->leftJoin('statuses', 'invoices.payment_status_id', '=', 'statuses.id')
        ->groupBy('status');
        
        $query = $this->applyFilters($query, $request);
        
        $data = $query->get();
        
        return response()->json([
            'data' => $data
        ]);
    }
    
    /**
     * Get payment method statistics
     */
    public function paymentMethods(Request $request)
    {
        $query = Invoice::select(
            'payment_methods.slug as method',
            'payment_methods.name as method_name',
            DB::raw('SUM(invoices.payment_amount) as total'),
            DB::raw('COUNT(*) as count')
        )
        ->leftJoin('payment_methods', 'invoices.payment_method_id', '=', 'payment_methods.id')
        ->where('invoices.user_id', Auth::id())
        ->whereNotNull('invoices.payment_method_id')
        ->groupBy('method', 'method_name')
        ->orderBy('total', 'desc');
        
        $query = $this->applyFilters($query, $request);
        
        $data = $query->get();
        
        return response()->json([
            'data' => $data
        ]);
    }
    
    /**
     * Get revenue vs expenses statistics
     */
    public function revenueExpenses(Request $request)
    {
        // Get revenue data - use issue_date instead of invoice_date
        $revenueQuery = Invoice::select(
            DB::raw('DATE_FORMAT(STR_TO_DATE(issue_date, "%Y-%m-%d"), "%Y-%m") as month'),
            DB::raw('SUM(payment_amount) as amount'),
            DB::raw('"revenue" as type')
        )
        ->where('user_id', Auth::id())
        ->groupBy('month');
        
        $revenueQuery = $this->applyFilters($revenueQuery, $request);
        
        // Get expense data - if Expense model exists
        // If Expense model does not exist, we will handle it gracefully
        try {
            $expenseQuery = Expense::select(
                DB::raw('DATE_FORMAT(expense_date, "%Y-%m") as month'),
                DB::raw('SUM(amount) as amount'),
                DB::raw('"expense" as type')
            )
            ->groupBy('month');
            
            $expenseQuery = $this->applyTimeRangeFilter($expenseQuery, $request);
            
            $expenseData = $expenseQuery->get();
        } catch (\Exception $e) {
            // If Expense model does not exist, we will create an empty collection
            $expenseData = collect([]);
        }
        
        // Union the queries
        $revenueData = $revenueQuery->get();
        
        $combinedData = $revenueData->concat($expenseData)->sortBy('month')->values();
        
        return response()->json([
            'data' => $combinedData
        ]);
    }
    
    /**
     * Apply common filters to queries based on request
     */
    private function applyFilters($query, Request $request)
    {
        $query = $this->applyTimeRangeFilter($query, $request);
        
        // Client filter
        if ($request->has('clients')) {
            $clients = $request->input('clients');
            if (!empty($clients)) {
                $query->whereIn('client_id', $clients);
            }
        }
        
        return $query;
    }
    
    /**
     * Apply time range filter to query
     */
    private function applyTimeRangeFilter($query, Request $request)
    {
        $modelTable = $query->getModel()->getTable();
        $dateColumn = '';
        
        // Determine the correct date column based on the table
        if ($modelTable === 'invoices') {
            $dateColumn = 'issue_date'; // Use issue_date instead of invoice_date
        } elseif ($modelTable === 'expenses') {
            $dateColumn = 'expense_date';
        } else {
            // Fallback for other tables
            return $query;
        }
        
        if ($request->has('date_from') && $request->has('date_to')) {
            // Custom date range
            $dateFrom = $request->input('date_from');
            $dateTo = $request->input('date_to');
            if ($modelTable === 'invoices') {
                // For invoices we need to work with varchar data type
                return $query->where(function($q) use ($dateColumn, $dateFrom, $dateTo) {
                    $q->whereRaw("STR_TO_DATE($dateColumn, '%Y-%m-%d') >= ?", [$dateFrom])
                      ->whereRaw("STR_TO_DATE($dateColumn, '%Y-%m-%d') <= ?", [$dateTo]);
                });
            } else {
                // For other tables we assume date type
                return $query->whereBetween($dateColumn, [$dateFrom, $dateTo]);
            }
        }
        
        // Predefined time ranges
        $timeRange = $request->input('time_range', 'year');
        $now = Carbon::now();
        
        switch ($timeRange) {
            case '6month':
                $startDate = $now->copy()->subMonths(6)->startOfMonth();
                break;
            case 'quarter':
                $startDate = $now->copy()->subMonths(3)->startOfMonth();
                break;
            case 'month':
                $startDate = $now->copy()->subMonth()->startOfMonth();
                break;
            case 'year':
            default:
                $startDate = $now->copy()->subYear()->startOfMonth();
                break;
        }
        
        if ($modelTable === 'invoices') {
            // For invoices we need to work with varchar data type
            return $query->whereRaw("STR_TO_DATE($dateColumn, '%Y-%m-%d') >= ?", [$startDate->format('Y-m-d')]);
        } else {
            // For other tables we assume date type
            return $query->where($dateColumn, '>=', $startDate->format('Y-m-d'));
        }
    }
}
