<?php

namespace App\Livewire\Invoice;

use App\Livewire\Shared\Paginates;
use App\Livewire\Shared\Sorts;
use App\Models\Invoice;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Url;
use Livewire\Component;
use Throwable;

/**
 * InvoiceList Livewire Component
 *
 * Handles displaying and filtering a paginated list of invoices for the authenticated user.
 * Provides search, filtering by status, sorting, and pagination functionality.
 */
class InvoiceList extends Component
{
    use Paginates;
    use Sorts;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url(as: 'sort')]
    public string $orderBy = 'created_at';

    #[Url(as: 'direction')]
    public bool $orderAsc = false;

    public ?string $errorMessage = null;

    /**
     * Reset pagination when search term is updated
     */
    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Reset pagination when status filter is updated
     */
    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    /**
     * Reset all filters and pagination
     */
    public function resetFilters(): void
    {
        $this->search = '';
        $this->status = '';
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

    /**
     * Render the component with invoices data
     */
    public function render(): View
    {
        try {
            $query = Invoice::with([
                'paymentMethod',
                'paymentStatus',
            ])
                ->where('user_id', Auth::id());

            if (! empty($this->search)) {
                $query->where(function ($q): void {
                    $q->where(
                        'invoice_vs',
                        'like',
                        "%{$this->search}%",
                    )
                        ->orWhere(
                            'customer_name',
                            'like',
                            "%{$this->search}%",
                        );
                });
            }

            if (! empty($this->status)) {
                $query->whereHas('paymentStatus', function ($q): void {
                    $q->where('name', $this->status);
                });
            }

            // Special handling for different sorting fields
            if ($this->sortKey() === 'due_date') {
                // Special handling for due_date which is calculated from issue_date + due_in
                $query->orderByRaw('DATE_ADD(issue_date, INTERVAL due_in DAY) ' . $this->sortDirection());
            } else {
                $query->orderBy($this->sortColumn(), $this->sortDirection());
            }

            $invoices = $query->paginate(10);

            return view('livewire.invoice.invoice-list', [
                'invoices' => $invoices,
                'hasData' => $invoices->total() > 0,
            ]);
        } catch (Throwable $e) {
            Log::error('Loading invoices failed', ['exception' => $e]);
            $this->errorMessage = __('invoices.messages.error_loading');

            return view('livewire.invoice.invoice-list', [
                'invoices' => Invoice::where('id', 0)->paginate(10), // Empty paginator
                'hasData' => false,
                'errorMessage' => $this->errorMessage,
            ]);
        }
    }
}
