<?php

namespace App\Livewire\Product;

use App\Livewire\Shared\Paginates;
use App\Livewire\Shared\Sorts;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Url;
use Livewire\Component;
use Throwable;

/**
 * ProductList Livewire Component
 *
 * Displays a paginated list of products with search, filtering, and sorting functionality.
 * Allows users to search by name or description, filter by status, and sort by various fields.
 */
class ProductList extends Component
{
    use Paginates;
    use Sorts;

    /**
     * Search term for filtering products
     */
    #[Url]
    public string $search = '';

    /**
     * Status filter for products
     */
    #[Url]
    public string $status = '';

    /**
     * Current sorting field
     */
    #[Url(as: 'sort')]
    public string $orderBy = 'created_at';

    /**
     * Sort direction (true = ascending, false = descending)
     */
    #[Url(as: 'direction')]
    public bool $orderAsc = false;

    /**
     * Current page for pagination
     */
    #[Url(keep: true)]
    public int $page = 1;

    /**
     * Error message to display to user
     */
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
            'name' => 'name',
            'category_id' => 'category_id',
            'supplier_id' => 'supplier_id',
            'price' => 'price',
            'tax_id' => 'tax_id',
            'is_default' => 'is_default',
            'invoices' => 'invoices_count',
        ];
    }

    protected function defaultSort(): string
    {
        return 'created_at';
    }

    /**
     * Render the component with products data
     */
    public function render(): View
    {
        try {
            $query = Product::query()
                ->where('user_id', Auth::id())
                ->withCount('invoices');

            if (! empty($this->search)) {
                $query->where(function ($q): void {
                    $q->where(
                        'name',
                        'like',
                        "%{$this->search}%",
                    )
                        ->orWhere(
                            'description',
                            'like',
                            "%{$this->search}%",
                        );
                });
            }

            if (! empty($this->status)) {
                if ($this->status === 'active') {
                    $query->where('is_active', true);
                } elseif ($this->status === 'inactive') {
                    $query->where('is_active', false);
                }
            }

            $query->orderBy($this->sortColumn(), $this->sortDirection());

            $products = $query->paginate(10);

            return view('livewire.product.product-list', [
                'products' => $products,
                'hasData' => $products->total() > 0,
            ]);
        } catch (Throwable $e) {
            Log::error('Loading products failed', ['exception' => $e]);
            $this->errorMessage = __('products.messages.error_loading');

            return view('livewire.product.product-list', [
                'products' => Product::where('id', 0)->paginate(10), // Empty paginator
                'hasData' => false,
                'errorMessage' => $this->errorMessage,
            ]);
        }
    }
}
