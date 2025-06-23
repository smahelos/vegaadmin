<?php

namespace App\Livewire;

use App\Models\Invoice;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Illuminate\Contracts\View\View;

/**
 * InvoiceListRecent Livewire Component
 * 
 * Displays recent invoices with sorting functionality.
 * Shows last 5 invoices for authenticated user with pagination support.
 */
class InvoiceListRecent extends Component
{
    use WithPagination;
    
    /**
     * Current sorting field
     *
     * @var string
     */
    #[Url(as: 'sort')]
    public string $orderBy = 'created_at';
    
    /**
     * Sort direction (true = ascending, false = descending)
     *
     * @var bool
     */
    #[Url(as: 'direction')]
    public bool $orderAsc = false;
    
    /**
     * Error message to display to user
     *
     * @var string|null
     */
    public ?string $errorMessage = null;
    
    /**
     * Set up component with pagination theme
     */
    public function mount(): void
    {
        $this->paginationTheme = 'bootstrap';
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
     * Render the component with recent invoices data
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function render(): View
    {
        try {
            $query = Invoice::with(['client', 'paymentMethod', 'paymentStatus'])
                ->where('user_id', Auth::id());
                
            $query->orderBy($this->orderBy, $this->orderAsc ? 'asc' : 'desc');
            
            $invoices = $query->paginate(5);
            
            return view('livewire.invoice-list-recent', [
                'invoices' => $invoices,
                'hasData' => $invoices->total() > 0,
                'errorMessage' => $this->errorMessage,
            ]);
        } catch (\Exception $e) {
            $this->errorMessage = 'Error while loading recent invoices list.';
            
            return view('livewire.invoice-list-recent', [
                'invoices' => Invoice::where('id', 0)->paginate(10), // Empty paginator
                'hasData' => false,
                'errorMessage' => $this->errorMessage
            ]);
        }
    }
}
