<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Analytics\Repositories;

use App\Domain\Analytics\Contracts\AnalyticsReadRepositoryInterface;
use App\Domain\Analytics\DTO\PlatformStatisticsDTO;
use App\Domain\Analytics\DTO\UserActivityStatisticsDTO;
use App\Domain\Analytics\DTO\UserStatisticsDTO;
use App\Domain\Shared\Authorization\ValueObjects\AllOwnersScope;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Infrastructure\Persistence\Eloquent\Analytics\Mappers\EloquentAnalyticsMapper;
use App\Infrastructure\Persistence\Eloquent\Analytics\Queries\CustomerRevenueQuery;
use App\Models\Business;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserActivitySummary;
use DateTimeImmutable;

final class EloquentAnalyticsRepository implements AnalyticsReadRepositoryInterface
{
    public function getUserStats(ReadScope $scope): UserStatisticsDTO
    {
        $query = Invoice::where('user_id', $scope->owner->value);
        $invoices = $query->count();
        $query = Contact::where('user_id', $scope->owner->value);
        $contacts = $query->count();
        $query = Business::where('user_id', $scope->owner->value);
        $businesses = $query->count();
        $query = Invoice::where('user_id', $scope->owner->value);
        $revenue = $query->sum('payment_amount');

        return new UserStatisticsDTO(
            $invoices,
            $contacts,
            $businesses,
            Money::fromString(
                (string) $revenue,
                'CZK',
            ),
        );
    }

    public function getCustomersWithTotals(ReadScope $scope): array
    {
        $query = Invoice::where('invoices.user_id', $scope->owner->value);
        $query = CustomerRevenueQuery::group($query);
        $query->orderByDesc('total');
        $baseQuery = $query->toBase();
        $rows = $baseQuery->get();
        $customers = $rows->map(
            fn (object $row) => EloquentAnalyticsMapper::customer(
                $row->contact_id === null ? null : (int) $row->contact_id,
                (string) $row->customer_name,
                (string) $row->total,
            ),
        );
        $customers = $customers->all();

        // The dashboard filter also offers contacts that do not yet have invoices.
        $query = Contact::where('user_id', $scope->owner->value);
        $contactIds = $rows->pluck('contact_id');
        $contactIds = $contactIds->filter();
        $query->whereNotIn(
            'id',
            $contactIds->all(),
        );
        $query->orderBy('name');
        $contacts = $query->get();
        foreach ($contacts as $contact) {
            $customers[] = EloquentAnalyticsMapper::customer(
                $contact->id,
                $contact->name,
                '0',
            );
        }

        return $customers;
    }

    public function getPlatformStatistics(
        AllOwnersScope $scope,
        DateTimeImmutable $recentSince,
        DateTimeImmutable $monthStart,
    ): PlatformStatisticsDTO {
        $invoices = Invoice::count();
        $query = Contact::whereNull('archived_at');
        $contacts = $query->count();
        $query = Business::whereNull('archived_at');
        $businesses = $query->count();
        $users = User::count();
        $query = Invoice::where(
            'created_at',
            '>=',
            $recentSince,
        );
        $recentInvoices = $query->count();
        $query = Contact::whereNull('archived_at');
        $query->where(
            'created_at',
            '>=',
            $recentSince,
        );
        $recentContacts = $query->count();
        $query = Subscription::where('status', 'active');
        $activeSubscriptions = $query->count();

        return new PlatformStatisticsDTO(
            $invoices,
            $contacts,
            $businesses,
            $users,
            $recentInvoices,
            $recentContacts,
            $activeSubscriptions,
            $this->revenueByCurrency(null),
            $this->revenueByCurrency($monthStart),
        );
    }

    public function getUserActivity(AllOwnersScope $scope, int $mostActiveLimit): UserActivityStatisticsDTO
    {
        $users = UserActivitySummary::count();
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
        $query = UserActivitySummary::where(
            'invoices_last_30_days',
            '>',
            0,
        );
        $query->orderByDesc('invoices_last_30_days');
        $query->limit($mostActiveLimit);
        $summaries = $query->get();
        $mostActive = $summaries->map(
            fn (UserActivitySummary $summary) => EloquentAnalyticsMapper::userActivity($summary),
        );

        return new UserActivityStatisticsDTO(
            $users,
            $activeUsers,
            $highActivityUsers,
            $inactiveUsers,
            array_values(
                $mostActive->all(),
            ),
        );
    }

    /**
     * @return list<Money> one sum per currency, ordered by its code
     */
    private function revenueByCurrency(?DateTimeImmutable $since): array
    {
        $query = Invoice::query();
        if ($since !== null) {
            $query->where(
                'created_at',
                '>=',
                $since,
            );
        }
        $query->select('payment_currency');
        $query->selectRaw('COALESCE(SUM(payment_amount), 0) AS total');
        $query->groupBy('payment_currency');
        $query->orderBy('payment_currency');
        $baseQuery = $query->toBase();
        $rows = $baseQuery->get();
        $revenue = [];
        foreach ($rows as $row) {
            $revenue[] = Money::fromString(
                (string) $row->total,
                (string) $row->payment_currency,
            );
        }

        return $revenue;
    }
}
