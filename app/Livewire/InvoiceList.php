<?php

namespace App\Livewire;

use App\Models\Invoice;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;

/**
 * InvoiceList Livewire Component
 * 
 * Handles displaying and filtering a paginated list of invoices for the authenticated user.
 * Provides search, filtering by status, sorting, and pagination functionality.
 */
class InvoiceList extends Component
{
    use WithPagination;
    
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
    public function mount(): void
    {
        // Set theme for pagination to ensure correct styling
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
            $query = Invoice::with(['client', 'paymentMethod', 'paymentStatus'])
                ->where('user_id', Auth::id());
                
            if (!empty($this->search)) {
                $query->where(function($q) {
                    $q->where('invoice_vs', 'like', "%{$this->search}%")
                      ->orWhereHas('client', function($q) {
                          $q->where('name', 'like', "%{$this->search}%");
                      });
                });
            }
            
            if (!empty($this->status)) {
                $query->whereHas('paymentStatus', function($q) {
                    $q->where('name', $this->status);
                });
            }
            
            // Special handling for different sorting fields
            if ($this->orderBy === 'client_id') {
                // Using subquery to sort by client name while maintaining eager loading
                $query->orderBy(function($query) {
                    $query->select('name')
                          ->from('clients')
                          ->whereColumn('clients.id', 'invoices.client_id')
                          ->limit(1);
                }, $this->orderAsc ? 'asc' : 'desc');
            } elseif ($this->orderBy === 'due_date') {
                // Special handling for due_date which is calculated from issue_date + due_in
                $query->orderByRaw('DATE_ADD(issue_date, INTERVAL due_in DAY) ' . ($this->orderAsc ? 'ASC' : 'DESC'));
            } else {
                $query->orderBy($this->orderBy, $this->orderAsc ? 'asc' : 'desc');
            }
            
            $invoices = $query->paginate(10);
            
            return view('livewire.invoice-list', [
                'invoices' => $invoices,
                'hasData' => $invoices->total() > 0,
            ]);
        } catch (\Exception $e) {
            $this->errorMessage = 'Error while loading invoices list.';
            
            return view('livewire.invoice-list', [
                'invoices' => Invoice::where('id', 0)->paginate(10), // Empty paginator
                'hasData' => false,
                'errorMessage' => $this->errorMessage
            ]);
        }
    }
}
