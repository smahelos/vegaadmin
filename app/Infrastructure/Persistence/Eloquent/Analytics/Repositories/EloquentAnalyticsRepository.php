<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Analytics\Repositories;

use App\Domain\Analytics\Contracts\AnalyticsReadRepositoryInterface;
use App\Domain\Analytics\DTO\UserStatisticsDTO;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\User\ValueObjects\UserId;
use App\Infrastructure\Persistence\Eloquent\Analytics\Mappers\EloquentAnalyticsMapper;
use App\Infrastructure\Persistence\Eloquent\Analytics\Queries\CustomerRevenueQuery;
use App\Models\Business;
use App\Models\Contact;
use App\Models\Invoice;

final class EloquentAnalyticsRepository implements AnalyticsReadRepositoryInterface
{
    public function getUserStats(UserId $userId): UserStatisticsDTO
    {
        $query = Invoice::where('user_id', $userId->toInt());
        $invoices = $query->count();
        $query = Contact::where('user_id', $userId->toInt());
        $contacts = $query->count();
        $query = Business::where('user_id', $userId->toInt());
        $businesses = $query->count();
        $query = Invoice::where('user_id', $userId->toInt());
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

    public function getCustomersWithTotals(UserId $userId): array
    {
        $query = Invoice::where('invoices.user_id', $userId->toInt());
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
        $query = Contact::where('user_id', $userId->toInt());
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
}
