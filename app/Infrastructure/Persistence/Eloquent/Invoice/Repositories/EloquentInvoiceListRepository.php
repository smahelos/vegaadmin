<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Invoice\Repositories;

use App\Domain\Invoice\Contracts\InvoiceListReadRepositoryInterface;
use App\Domain\Invoice\DTO\InvoiceSearch;
use App\Domain\Shared\Pagination\DTO\PageRequest;
use App\Domain\Shared\Pagination\DTO\PaginatedResult;
use App\Domain\User\ValueObjects\UserId;
use App\Infrastructure\Persistence\Eloquent\Invoice\Mappers\EloquentInvoiceListMapper;
use App\Models\Business;
use App\Models\Contact;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Builder;

final class EloquentInvoiceListRepository implements InvoiceListReadRepositoryInterface
{
    public function searchForUser(
        UserId $userId,
        InvoiceSearch $search,
        PageRequest $page,
    ): PaginatedResult {
        $query = Invoice::query()->with(['paymentMethod', 'paymentStatus'])->where('user_id', $userId->toInt());
        if ($search->term !== '') {
            $query->where(static function (Builder $query) use ($search): void {
                $query->where(
                    'invoice_vs',
                    'like',
                    '%' . $search->term . '%',
                )
                    ->orWhere(
                        'customer_name',
                        'like',
                        '%' . $search->term . '%',
                    );
            });
        }
        if ($search->status !== null) {
            $query->whereHas('paymentStatus', static function (Builder $query) use ($search): void {
                $query->where('slug', $search->status->value);
            });
        }
        if ($search->businessId !== null) {
            $query->where('business_id', $search->businessId->value);
        }
        if ($search->contactId !== null) {
            $query->where('contact_id', $search->contactId->value);
        }

        $sort = in_array(
            $search->sort,
            [
                'created_at',
                'invoice_vs',
                'customer_name',
                'payment_amount',
                'payment_method_id',
                'payment_status_id',
                'due_date',
            ],
            true,
        ) ? $search->sort : 'created_at';
        $direction = $search->ascending ? 'asc' : 'desc';
        if ($sort === 'due_date') {
            $query->orderByRaw('DATE_ADD(issue_date, INTERVAL due_in DAY) ' . $direction);
        } else {
            $query->orderBy($sort, $direction);
        }
        $result = $query->orderBy('id', $direction)->paginate(
            $page->perPage(),
            ['*'],
            'page',
            $page->page(),
        );

        return new PaginatedResult(
            $result->getCollection()->map(EloquentInvoiceListMapper::toDTO(...))->all(),
            $result->total(),
            $result->perPage(),
            $result->currentPage(),
        );
    }

    public function businessFiltersForUser(UserId $userId): array
    {
        return Business::query()->where('user_id', $userId->toInt())->orderBy('name')->orderBy('id')
            ->get([
                'id',
                'name',
                'archived_at',
            ])->map(EloquentInvoiceListMapper::businessFilter(...))->all();
    }

    public function contactFiltersForUser(UserId $userId): array
    {
        return Contact::query()->where('user_id', $userId->toInt())->orderBy('name')->orderBy('id')
            ->get([
                'id',
                'name',
                'archived_at',
            ])->map(EloquentInvoiceListMapper::contactFilter(...))->all();
    }
}
