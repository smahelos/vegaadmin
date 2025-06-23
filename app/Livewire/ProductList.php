<?php

namespace App\Livewire;

use App\Models\Product;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Illuminate\Contracts\View\View;

/**
 * ProductList Livewire Component
 * 
 * Displays a paginated list of products with search, filtering, and sorting functionality.
 * Allows users to search by name or description, filter by status, and sort by various fields.
 */
class ProductList extends Component
{
    use WithPagination;
    
    /**
     * Search term for filtering products
     *
     * @var string
     */
    #[Url]
    public string $search = '';
    
    /**
     * Status filter for products
     *
     * @var string
     */
    #[Url]
    public string $status = '';
    
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
     * Current page for pagination
     *
     * @var int
     */
    #[Url(keep: true)]
    public int $page = 1;
    
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
     * Render the component with products data
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function render(): View
    {
        try {
            $query = Product::query()
                ->where('user_id', Auth::id())
                ->withCount('invoices');
            
            if (!empty($this->search)) {
                $query->where(function($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                      ->orWhere('description', 'like', "%{$this->search}%");
                });
            }
        
            if (!empty($this->status)) {
                if ($this->status === 'active') {
                    $query->where('is_active', true);
                } elseif ($this->status === 'inactive') {
                    $query->where('is_active', false);
                }
            }

            // Handle special sort cases
            if ($this->orderBy === 'invoices') {
                $query->orderBy('invoices_count', $this->orderAsc ? 'asc' : 'desc');
            } else {
                $query->orderBy($this->orderBy, $this->orderAsc ? 'asc' : 'desc');
            }
            
            $products = $query->paginate(10);
            
            return view('livewire.product-list', [
                'products' => $products,
                'hasData' => $products->total() > 0,
            ]);
        } catch (\Exception $e) {
            $this->errorMessage = 'Error while loading products.';
            
            return view('livewire.product-list', [
                'products' => Product::where('id', 0)->paginate(10), // Empty paginator
                'hasData' => false,
                'errorMessage' => $this->errorMessage
            ]);
        }
    }
}
