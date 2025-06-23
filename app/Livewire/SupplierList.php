<?php

namespace App\Livewire;

use App\Models\Supplier;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;

class SupplierList extends Component
{
    use WithPagination;
    
    #[Url]
    public $search = '';
    
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
    
    /**
     * Reset all filters and pagination
     */
    public function resetFilters(): void
    {
        $this->search = '';
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
            $query = Supplier::where('user_id', Auth::id())
                ->withCount('invoices');
            
            if ($this->search) {
                $query->where(function($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                      ->orWhere('email', 'like', "%{$this->search}%")
                      ->orWhere('ico', 'like', "%{$this->search}%");
                });
            }
            
            // Handle special sort cases
            if ($this->orderBy === 'invoices') {
                // Sort by the number of invoices
                $query->orderBy('invoices_count', $this->orderAsc ? 'asc' : 'desc');
            } else {
                // Standard sorting for other fields
                $query->orderBy($this->orderBy, $this->orderAsc ? 'asc' : 'desc');
            }
            
            $suppliers = $query->paginate(10);
            
            return view('livewire.supplier-list', [
                'suppliers' => $suppliers,
                'hasData' => $suppliers->total() > 0,
            ]);
        } catch (\Exception $e) {
            $this->errorMessage = 'Error while loading suppliers.';
            
            return view('livewire.supplier-list', [
                'suppliers' => collect(),
                'hasData' => false,
                'errorMessage' => $this->errorMessage
            ]);
        }
    }
}
