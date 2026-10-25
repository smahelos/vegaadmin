<?php

namespace App\Livewire\Invoice;

use App\Livewire\Shared\Paginates;
use App\Models\Invoice;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Throwable;

/**
 * InvoiceListRecent Livewire Component
 *
 * Displays recent invoices.
 * Shows last 5 invoices for authenticated user with pagination support.
 */
class InvoiceListRecent extends Component
{
    use Paginates;

    /**
     * Error message to display to user
     */
    public ?string $errorMessage = null;

    /**
     * Render the component with recent invoices data
     */
    public function render(): View
    {
        try {
            $query = Invoice::with([
                'client',
                'paymentMethod',
                'paymentStatus',
            ])
                ->where('user_id', Auth::id())
                ->latest();

            $invoices = $query->paginate(5);

            return view('livewire.invoice.invoice-list-recent', [
                'invoices' => $invoices,
                'hasData' => $invoices->total() > 0,
                'errorMessage' => $this->errorMessage,
            ]);
        } catch (Throwable $e) {
            Log::error('Loading recent invoices failed', ['exception' => $e]);
            $this->errorMessage = __('invoices.messages.error_loading');

            return view('livewire.invoice.invoice-list-recent', [
                'invoices' => Invoice::where('id', 0)->paginate(10), // Empty paginator
                'hasData' => false,
                'errorMessage' => $this->errorMessage,
            ]);
        }
    }
}
