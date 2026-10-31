<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Analytics\Repositories;

use App\Application\Analytics\Contracts\ApiStatisticsRepository;
use App\Domain\Invoice\Enums\InvoiceStatus;
use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\Shared\Time\Contracts\ClockInterface;
use App\Domain\User\ValueObjects\UserId;
use App\Infrastructure\Persistence\Eloquent\Analytics\Queries\CustomerRevenueQuery;
use App\Models\Expense;
use App\Models\Invoice;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class EloquentApiStatisticsRepository implements ApiStatisticsRepository
{
    /** Status bucket of unpaid invoices past their due date; overdue is derived, not stored */
    private const OVERDUE = 'overdue';

    public function __construct(
        private readonly ClockInterface $clock,
    ) {}

    public function monthlyRevenue(
        UserId $userId,
        DateTimeImmutable $from,
        ?DateTimeImmutable $to,
        array $contactIds,
    ): array {
        $query = $this->invoices(
            $userId,
            $from,
            $to,
            $contactIds,
        )
            ->select(
                DB::raw('DATE_FORMAT(invoices.issue_date, "%Y-%m") as month'),
                DB::raw('SUM(invoices.payment_amount) as total'),
            )
            ->selectRaw('SUM(CASE WHEN statuses.slug = ? THEN invoices.payment_amount ELSE 0 END) as paid', [InvoiceStatus::PAID->value])
            ->leftJoin(
                'statuses',
                'invoices.payment_status_id',
                '=',
                'statuses.id',
            )
            ->groupBy('month')
            ->orderBy('month');

        return $this->rows($query);
    }

    public function customerRevenue(
        UserId $userId,
        DateTimeImmutable $from,
        ?DateTimeImmutable $to,
        array $contactIds,
    ): array {
        $query = $this->invoices(
            $userId,
            $from,
            $to,
            $contactIds,
        );
        $query = CustomerRevenueQuery::group($query)
            ->orderByDesc('total')
            ->limit(10);

        return $this->rows($query);
    }

    public function revenue(
        UserId $userId,
        DateTimeImmutable $from,
        ?DateTimeImmutable $to,
        array $contactIds,
    ): array {
        $query = $this->invoices(
            $userId,
            $from,
            $to,
            $contactIds,
        )
            ->select(
                DB::raw('DATE_FORMAT(invoices.issue_date, "%Y-%m") as month'),
                DB::raw('SUM(invoices.payment_amount) as amount'),
                DB::raw('"revenue" as type'),
            )
            ->groupBy('month');

        return $this->rows($query);
    }

    public function expenses(
        UserId $userId,
        DateTimeImmutable $from,
        ?DateTimeImmutable $to,
    ): array {
        $query = Expense::query()
            ->where('expenses.user_id', $userId->toInt())
            ->where(
                'expenses.expense_date',
                '>=',
                $from->format('Y-m-d'),
            )
            ->when($to !== null, fn (Builder $query) => $query->where('expenses.expense_date', '<=', $to?->format('Y-m-d')))
            ->select(
                DB::raw('DATE_FORMAT(expenses.expense_date, "%Y-%m") as month'),
                DB::raw('SUM(expenses.amount) as amount'),
                DB::raw('"expense" as type'),
            )
            ->groupBy('month');

        return $this->rows($query);
    }

    public function invoiceStatus(
        UserId $userId,
        DateTimeImmutable $from,
        ?DateTimeImmutable $to,
        array $contactIds,
    ): array {
        $query = $this->invoices(
            $userId,
            $from,
            $to,
            $contactIds,
        )
            ->selectRaw(
                'CASE WHEN statuses.slug IN (?, ?) AND DATE_ADD(invoices.issue_date, INTERVAL invoices.due_in DAY) < ? THEN ? ELSE statuses.slug END as status',
                [
                    InvoiceStatus::PENDING->value,
                    InvoiceStatus::PARTIALLY_PAID->value,
                    $this->clock->now()->format('Y-m-d'),
                    self::OVERDUE,
                ],
            )
            ->selectRaw('COUNT(*) as count')
            ->leftJoin(
                'statuses',
                'invoices.payment_status_id',
                '=',
                'statuses.id',
            )
            ->groupBy('status');

        return $this->rows($query);
    }

    public function paymentMethods(
        UserId $userId,
        DateTimeImmutable $from,
        ?DateTimeImmutable $to,
        array $contactIds,
    ): array {
        $query = $this->invoices(
            $userId,
            $from,
            $to,
            $contactIds,
        )
            ->select(
                'payment_methods.slug as method',
                'payment_methods.name as method_name',
                DB::raw('SUM(invoices.payment_amount) as total'),
                DB::raw('COUNT(*) as count'),
            )
            ->leftJoin(
                'payment_methods',
                'invoices.payment_method_id',
                '=',
                'payment_methods.id',
            )
            ->whereNotNull('invoices.payment_method_id')
            ->groupBy('method', 'method_name')
            ->orderBy('total', 'desc');

        return $this->rows($query);
    }

    /**
     * Invoices of the user in the date range, optionally of the given customers only.
     *
     * @param  list<ContactId>  $contactIds
     * @return Builder<Invoice>
     */
    private function invoices(
        UserId $userId,
        DateTimeImmutable $from,
        ?DateTimeImmutable $to,
        array $contactIds,
    ): Builder {
        return Invoice::query()
            ->where('invoices.user_id', $userId->toInt())
            ->where(
                'invoices.issue_date',
                '>=',
                $from->format('Y-m-d'),
            )
            ->when($to !== null, fn (Builder $query) => $query->where('invoices.issue_date', '<=', $to?->format('Y-m-d')))
            ->when($contactIds !== [], fn (Builder $query) => $query->whereIn(
                'invoices.contact_id',
                array_map(static fn (ContactId $id): int => $id->value, $contactIds),
            ));
    }

    /**
     * @param  Builder<covariant Model>  $query
     * @return list<array<string, mixed>>
     */
    private function rows(Builder $query): array
    {
        return array_values(array_map(
            static fn (object $row): array => (array) $row,
            $query->toBase()->get()->all(),
        ));
    }
}
