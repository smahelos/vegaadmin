<?php

namespace App\Livewire;

use App\Models\Invoice;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;

class InvoiceList extends Component
{
    use WithPagination;
    
    #[Url]
    public $search = '';
    
    #[Url]
    public $status = '';
    
    #[Url(as: 'sort')]
    public $orderBy = 'created_at';
    
    #[Url(as: 'direction')]
    public $orderAsc = false;
    
    #[Url(keep: true)]
    public $page = 1;
    
    public $errorMessage = null;
    
    protected $paginationTheme = 'tailwind';
    
    public function mount()
    {
        // Set theme for pagination to ensure correct styling
        $this->paginationTheme = 'tailwind';
    }
    
    public function updatingSearch()
    {
        $this->resetPage();
    }
    
    public function updatingStatus()
    {
        $this->resetPage();
    }
    
    public function resetFilters()
    {
        $this->search = '';
        $this->status = '';
        $this->resetPage();
    }
    
    public function sortBy($field)
    {
        if ($this->orderBy === $field) {
            $this->orderAsc = !$this->orderAsc;
        } else {
            $this->orderBy = $field;
            $this->orderAsc = true;
        }
    }
    
    public function render()
    {
        try {
            $query = Invoice::with(['client', 'paymentMethod', 'paymentStatus'])
                ->where('user_id', Auth::id());
                
            if ($this->search) {
                $query->where(function($q) {
                    $q->where('number', 'like', "%{$this->search}%")
                      ->orWhereHas('client', function($q) {
                          $q->where('name', 'like', "%{$this->search}%");
                      });
                });
            }
            
            if ($this->status) {
                $query->where('status', $this->status);
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
