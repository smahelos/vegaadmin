<?php

declare(strict_types=1);

namespace App\Livewire\Invoice;

use App\Application\Invoice\Presenters\InvoiceListPresenter;
use App\Application\Invoice\Services\InvoiceListApplicationService;
use App\Domain\Invoice\DTO\InvoiceSearch;
use App\Domain\Invoice\Enums\InvoiceStatus;
use App\Domain\Party\Exceptions\BusinessNotFoundException;
use App\Domain\Party\Exceptions\ContactNotFoundException;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\Shared\Pagination\DTO\PageRequest;
use App\Livewire\Shared\ActsForUser;
use App\Livewire\Shared\Paginates;
use App\Livewire\Shared\Sorts;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

final class InvoiceList extends Component
{
    use ActsForUser;
    use Paginates;
    use Sorts;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url(as: 'business_id')]
    public string $businessId = '';

    #[Url(as: 'contact_id')]
    public string $contactId = '';

    #[Url(as: 'sort')]
    public string $orderBy = 'created_at';

    #[Url(as: 'direction')]
    public bool $orderAsc = false;

    public function updating(string $property): void
    {
        if (in_array(
            $property,
            [
                'search',
                'status',
                'businessId',
                'contactId',
            ],
            true,
        )) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->status = '';
        $this->businessId = '';
        $this->contactId = '';
        $this->resetPage();
    }

    protected function sortableColumns(): array
    {
        return [
            'created_at' => 'created_at',
            'invoice_vs' => 'invoice_vs',
            'customer_name' => 'customer_name',
            'payment_amount' => 'payment_amount',
            'payment_method_id' => 'payment_method_id',
            'payment_status_id' => 'payment_status_id',
            'due_date' => 'due_date',
        ];
    }

    protected function defaultSort(): string
    {
        return 'created_at';
    }

    public function render(InvoiceListApplicationService $invoices, InvoiceListPresenter $presenter): View
    {
        $this->requirePermission('frontend.can_view_invoice');
        $userId = $this->userId();
        $status = InvoiceStatus::tryFrom($this->status);
        abort_if($this->status !== '' && $status === null, 404);
        $businessId = $this->filterId($this->businessId);
        $contactId = $this->filterId($this->contactId);
        try {
            $result = $invoices->search(
                $userId,
                new InvoiceSearch(
                    $this->search,
                    $status,
                    $businessId === null ? null : BusinessId::fromInt($businessId),
                    $contactId === null ? null : ContactId::fromInt($contactId),
                    $this->sortKey(),
                    $this->orderAsc,
                ),
                new PageRequest($this->getPage(), 10),
            );
        } catch (BusinessNotFoundException|ContactNotFoundException) {
            abort(404);
        }

        return view('livewire.invoice.invoice-list', [
            'invoices' => $this->paginator($result),
            'hasData' => $result->total() > 0,
            'businesses' => $invoices->businessFilters($userId),
            'contacts' => $invoices->contactFilters($userId),
            'statuses' => InvoiceStatus::cases(),
            'presenter' => $presenter,
        ]);
    }

    private function filterId(string $value): ?int
    {
        if ($value === '') {
            return null;
        }
        $id = filter_var(
            $value,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]],
        );
        abort_if($id === false, 404);

        return $id;
    }
}
