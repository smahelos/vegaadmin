<?php

namespace App\Infrastructure\Persistence\Eloquent\Analytics\Repositories;

use App\Application\Analytics\Contracts\AnalyticsReadRepository;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;

class EloquentAnalyticsReadRepository implements AnalyticsReadRepository
{
    public function getUserStats(int $userId): array
    {
        return [
            'invoice_count' => Invoice::where('user_id', $userId)->count(),
            'client_count' => Client::where('user_id', $userId)->count(),
            'suppliers_count' => Supplier::where('user_id', $userId)->count(),
            'total_amount' => (float) Invoice::where('user_id', $userId)->sum('payment_amount'),
        ];
    }

    public function getMonthlyStats(int $userId, int $months): array
    {
        $defaultConnection = config('database.default');
        $connectionConfig = config('database.connections.' . $defaultConnection);
        $driver = $connectionConfig['driver'] ?? 'sqlite';
        $dateExpr = $driver === 'sqlite'
            ? 'strftime("%Y-%m", invoices.issue_date)'
            : 'DATE_FORMAT(invoices.issue_date, "%Y-%m")';

        $rows = DB::table('invoices')
            ->join('clients', 'invoices.client_id', '=', 'clients.id')
            ->where('clients.user_id', $userId)
            ->whereNotNull('invoices.issue_date')
            ->where('invoices.issue_date', '>=', now()->subMonths($months))
            ->select(DB::raw($dateExpr . ' as month'), DB::raw('SUM(invoices.payment_amount) as total'))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return $rows->map(fn ($r) => ['month' => (string)$r->month, 'total' => (float)$r->total])->all();
    }

    public function getClientsWithTotals(int $userId): array
    {
        $clients = Client::where('user_id', $userId)
            ->with(['invoices' => function ($query) {
                $query->select('client_id', DB::raw('SUM(payment_amount) as total'))
                    ->groupBy('client_id');
            }])
            ->get();

        return $clients->map(function (Client $c) {
            $total = 0.0;
            if ($c->relationLoaded('invoices')) {
                $agg = $c->invoices->first();
                if ($agg && isset($agg->total)) {
                    $total = (float) $agg->total;
                }
            }
            return [
                'client_id' => (int)$c->id,
                'client_name' => (string)$c->name,
                'total' => (float)$total,
            ];
        })->all();
    }
}
