<?php

namespace App\Livewire\Invoice;

use App\Models\Invoice;
use App\Models\Client;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;

/**
 * InvoiceListByClient Livewire Component
 * 
 * Displays a paginated list of invoices for a specific client.
 * Provides search, filtering by status, sorting, and pagination functionality.
 */
class InvoiceListByClient extends Component
{
    use WithPagination;
    
    public int $clientId;
    
    #[Url]
    public string $search = '';
    
    #[Url]
    public string $status = '';
    
    #[Url(as: 'sort')]
    public string $orderBy = 'created_at';
    
    #[Url(as: 'direction')]
    public bool $orderAsc = false;
    
    #[Url(keep: true)]
    public int $page = 1;
    
    public ?string $errorMessage = null;
    
    protected string $paginationTheme = 'tailwind';
    
    /**
     * Component initialization
     */
    public function mount(int $clientId): void
    {
        $this->clientId = $clientId;
        $this->paginationTheme = 'tailwind';
    }
    
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
    
    /**
     * Sort by given field, toggle direction if same field
     *
     * @param string $field
     */
    public function sortBy(string $field): void
    {
        if ($this->orderBy === $field) {
            $this->orderAsc = !$this->orderAsc;
        } else {
            $this->orderBy = $field;
            $this->orderAsc = true;
        }
    }
    
    /**
     * Render the component with invoices data
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function render(): \Illuminate\Contracts\View\View
    {
        try {
            // Verify client belongs to authenticated user
            $client = Client::where('user_id', Auth::id())
                ->where('id', $this->clientId)
                ->firstOrFail();
                
            $query = Invoice::with(['paymentMethod', 'paymentStatus'])
                ->where('user_id', Auth::id())
                ->where('client_id', $this->clientId);
                
            if (!empty($this->search)) {
                $query->where(function($q) {
                    $q->where('invoice_vs', 'like', "%{$this->search}%")
                      ->orWhere('description', 'like', "%{$this->search}%");
                });
            }
            
            if (!empty($this->status)) {
                $query->whereHas('paymentStatus', function($q) {
                    $q->where('name', $this->status);
                });
            }
            
            // Special handling for different sorting fields
            if ($this->orderBy === 'due_date') {
                // Special handling for due_date which is calculated from issue_date + due_in
                $query->orderByRaw('DATE_ADD(issue_date, INTERVAL due_in DAY) ' . ($this->orderAsc ? 'ASC' : 'DESC'));
            } else {
                $query->orderBy($this->orderBy, $this->orderAsc ? 'asc' : 'desc');
            }
            
            $invoices = $query->paginate(10);
            
            return view('livewire.invoice.invoice-list-by-client', [
                'invoices' => $invoices,
                'client' => $client,
                'hasData' => $invoices->total() > 0,
            ]);
        } catch (\Exception $e) {
            $this->errorMessage = 'Error while loading client invoices.';

            return view('livewire.invoice.invoice-list-by-client', [
                'invoices' => Invoice::where('id', 0)->paginate(10), // Empty paginator
                'client' => null,
                'hasData' => false,
                'errorMessage' => $this->errorMessage
            ]);
        }
    }
}
