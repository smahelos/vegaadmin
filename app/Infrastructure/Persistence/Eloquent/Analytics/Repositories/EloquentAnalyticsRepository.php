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
        return new UserStatisticsDTO(
            Invoice::where('user_id', $userId->toInt())->count(),
            Contact::where('user_id', $userId->toInt())->count(),
            Business::where('user_id', $userId->toInt())->count(),
            Money::fromString((string) Invoice::where('user_id', $userId->toInt())->sum('payment_amount'), 'CZK'),
        );
    }

    public function getCustomersWithTotals(UserId $userId): array
    {
        $rows = CustomerRevenueQuery::group(Invoice::where('invoices.user_id', $userId->toInt()))
            ->orderByDesc('total')->toBase()->get();
        $customers = $rows->map(fn (object $row) => EloquentAnalyticsMapper::customer(
            $row->contact_id === null ? null : (int) $row->contact_id,
            (string) $row->customer_name,
            (string) $row->total,
        ))->all();

        // The dashboard filter also offers contacts that do not yet have invoices.
        $contacts = Contact::where('user_id', $userId->toInt())
            ->whereNotIn('id', $rows->pluck('contact_id')->filter()->all())
            ->orderBy('name')->get();
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
