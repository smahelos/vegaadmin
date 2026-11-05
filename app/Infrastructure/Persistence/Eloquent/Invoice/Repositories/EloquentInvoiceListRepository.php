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
        $query = Invoice::query();
        $query->with(['paymentMethod', 'paymentStatus']);
        $query->where('user_id', $userId->toInt());
        if ($search->term !== '') {
            $query->where(
                static function (Builder $query) use ($search): void {
                    $query->where(
                        'invoice_vs',
                        'like',
                        '%' . $search->term . '%',
                    );
                    $query->orWhere(
                        'customer_name',
                        'like',
                        '%' . $search->term . '%',
                    );
                },
            );
        }
        if ($search->status !== null) {
            $query->whereHas(
                'paymentStatus',
                static function (Builder $query) use ($search): void {
                    $query->where('slug', $search->status->value);
                },
            );
        }
        if ($search->businessId !== null) {
            $query->where('business_id', $search->businessId->value);
        }
        if ($search->contactId !== null) {
            $query->where('contact_id', $search->contactId->value);
        }

        $sortable = in_array(
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
        );
        if ($sortable) {
            $sort = $search->sort;
        } else {
            $sort = 'created_at';
        }
        $direction = $search->ascending ? 'asc' : 'desc';
        if ($sort === 'due_date') {
            $query->orderByRaw('DATE_ADD(issue_date, INTERVAL due_in DAY) ' . $direction);
        } else {
            $query->orderBy($sort, $direction);
        }
        $query->orderBy('id', $direction);
        $result = $query->paginate(
            $page->perPage(),
            ['*'],
            'page',
            $page->page(),
        );

        $invoices = $result->getCollection();
        $invoices = $invoices->map(
            EloquentInvoiceListMapper::toDTO(...),
        );

        return new PaginatedResult(
            $invoices->all(),
            $result->total(),
            $result->perPage(),
            $result->currentPage(),
        );
    }

    public function businessFiltersForUser(UserId $userId): array
    {
        $query = Business::query();
        $query->where('user_id', $userId->toInt());
        $query->orderBy('name');
        $query->orderBy('id');
        $businesses = $query->get([
            'id',
            'name',
            'archived_at',
        ]);
        $businesses = $businesses->map(
            EloquentInvoiceListMapper::businessFilter(...),
        );

        return $businesses->all();
    }

    public function contactFiltersForUser(UserId $userId): array
    {
        $query = Contact::query();
        $query->where('user_id', $userId->toInt());
        $query->orderBy('name');
        $query->orderBy('id');
        $contacts = $query->get([
            'id',
            'name',
            'archived_at',
        ]);
        $contacts = $contacts->map(
            EloquentInvoiceListMapper::contactFilter(...),
        );

        return $contacts->all();
    }
}
