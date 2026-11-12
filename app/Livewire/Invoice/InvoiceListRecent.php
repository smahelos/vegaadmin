<?php

declare(strict_types=1);

namespace App\Livewire\Invoice;

use App\Application\Invoice\Presenters\InvoiceListPresenter;
use App\Application\Invoice\Queries\ListInvoices;
use App\Domain\Invoice\DTO\InvoiceSearch;
use App\Domain\Shared\Pagination\DTO\PageRequest;
use App\Livewire\Shared\ActsForUser;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * The owner's five newest invoices on the dashboard.
 */
final class InvoiceListRecent extends Component
{
    use ActsForUser;

    public function render(InvoiceListPresenter $presenter): View
    {
        $this->requireAllowed(ListInvoices::class);
        $result = $this->runUseCase(
            new ListInvoices(
                $this->userId(),
                new InvoiceSearch(
                    '',
                    null,
                    null,
                    null,
                    'created_at',
                    false,
                ),
                new PageRequest(1, 5),
            ),
        );

        return view(
            'livewire.invoice.invoice-list-recent',
            [
                'invoices' => $result->items(),
                'presenter' => $presenter,
            ],
        );
    }
}
