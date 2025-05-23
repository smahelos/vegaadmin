<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Client;
use App\Models\Supplier;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StatisticsController extends Controller
{
    /**
     * Get monthly revenue statistics
     */
    public function monthlyRevenue(Request $request)
    {
        $query = Invoice::select(
            // Změna formátu pole `issue_date`, které je varchar
            DB::raw('DATE_FORMAT(STR_TO_DATE(issue_date, "%Y-%m-%d"), "%Y-%m") as month'),
            DB::raw('SUM(payment_amount) as total'),
            // Spojení s tabulkou statuses pro kontrolu stavu platby
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
     */
    public function clientRevenue(Request $request)
    {
        $query = Invoice::select(
            'clients.id as client_id',
            'clients.name as client_name',
            DB::raw('SUM(invoices.payment_amount) as total')
        )
        ->join('clients', 'invoices.client_id', '=', 'clients.id')
        ->groupBy('client_id', 'client_name')
        ->orderBy('total', 'desc')
        ->limit(10);  // Limit to top 10 clients
        
        $query = $this->applyFilters($query, $request);
        
        $data = $query->get();
        
        return response()->json([
            'data' => $data
        ]);
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
        // Get revenue data - používáme issue_date místo invoice_date
        $revenueQuery = Invoice::select(
            DB::raw('DATE_FORMAT(STR_TO_DATE(issue_date, "%Y-%m-%d"), "%Y-%m") as month'),
            DB::raw('SUM(payment_amount) as amount'),
            DB::raw('"revenue" as type')
        )
        ->groupBy('month');
        
        $revenueQuery = $this->applyFilters($revenueQuery, $request);
        
        // Get expense data - pokud existuje model Expense
        // Pokud Expense model neexistuje, můžete vytvořit prázdný výsledek
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
            // Pokud model Expense neexistuje, vytvoříme prázdnou kolekci
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
        
        // Určíme správný datový sloupec podle tabulky
        if ($modelTable === 'invoices') {
            $dateColumn = 'issue_date'; // Používáme issue_date místo invoice_date
        } elseif ($modelTable === 'expenses') {
            $dateColumn = 'expense_date';
        } else {
            // Fallback pro jiné tabulky
            return $query;
        }
        
        if ($request->has('date_from') && $request->has('date_to')) {
            // Custom date range
            $dateFrom = $request->input('date_from');
            $dateTo = $request->input('date_to');
            
            if ($modelTable === 'invoices') {
                // Pro invoice musíme pracovat s varchar datovým typem
                return $query->where(function($q) use ($dateColumn, $dateFrom, $dateTo) {
                    $q->whereRaw("STR_TO_DATE($dateColumn, '%Y-%m-%d') >= ?", [$dateFrom])
                      ->whereRaw("STR_TO_DATE($dateColumn, '%Y-%m-%d') <= ?", [$dateTo]);
                });
            } else {
                // Pro ostatní tabulky předpokládáme date typ
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
            // Pro invoice musíme pracovat s varchar datovým typem
            return $query->whereRaw("STR_TO_DATE($dateColumn, '%Y-%m-%d') >= ?", [$startDate->format('Y-m-d')]);
        } else {
            // Pro ostatní tabulky předpokládáme date typ
            return $query->where($dateColumn, '>=', $startDate->format('Y-m-d'));
        }
    }
}
