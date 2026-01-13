<?php

namespace App\Infrastructure\Persistence\Eloquent\Analytics\Repositories;

use App\Application\Analytics\Contracts\ApiStatisticsRepository;
use App\Models\Expense;
use App\Models\Invoice;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EloquentApiStatisticsRepository implements ApiStatisticsRepository
{
    public function getMonthlyRevenueQuery(): mixed
    {
        $query = Invoice::select(
            DB::raw('DATE_FORMAT(STR_TO_DATE(issue_date, "%Y-%m-%d"), "%Y-%m") as month'),
            DB::raw('SUM(payment_amount) as total'),
            DB::raw('SUM(CASE WHEN statuses.slug = "paid" THEN payment_amount ELSE 0 END) as paid')
        )
            ->leftJoin('statuses', 'invoices.payment_status_id', '=', 'statuses.id')
            ->groupBy('month')
            ->orderBy('month');

        return $query;
    }

    public function getClientRevenueQuery($startDate): mixed
    {
        $query = Invoice::query()
            ->join('clients', 'invoices.client_id', '=', 'clients.id')
            ->select([
                'clients.id as client_id',
                'clients.name as client_name',
                DB::raw('SUM(invoices.payment_amount) as total')
            ])
            ->where('invoices.user_id', Auth::id())
            ->whereDate(DB::raw("STR_TO_DATE(invoices.issue_date, '%Y-%m-%d')"), '>=', $startDate)
            ->groupBy(['clients.id', 'clients.name'])
            ->orderByDesc('total')
            ->limit(10);

        return $query;
    }

    public function getRevenueQuery(): mixed
    {
        return Invoice::select(
            DB::raw('DATE_FORMAT(STR_TO_DATE(issue_date, "%Y-%m-%d"), "%Y-%m") as month'),
            DB::raw('SUM(payment_amount) as amount'),
            DB::raw('"revenue" as type')
        )
        ->where('user_id', Auth::id())
        ->groupBy('month');
    }

    public function getExpenseQuery(): mixed
    {
        return Expense::select(
            DB::raw('DATE_FORMAT(expense_date, "%Y-%m") as month'),
            DB::raw('SUM(amount) as amount'),
            DB::raw('"expense" as type')
        )
        ->groupBy('month');
    }

    public function getInvoiceStatusQuery(): mixed
    {
        $query = Invoice::select(
            'statuses.slug as status',
            DB::raw('COUNT(*) as count')
        )
            ->where('user_id', Auth::id())
            ->leftJoin('statuses', 'invoices.payment_status_id', '=', 'statuses.id')
            ->groupBy('status');

        return $query;
    }

    public function getPaymentMethodsQuery(): mixed
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

        return $query;
    }
}
