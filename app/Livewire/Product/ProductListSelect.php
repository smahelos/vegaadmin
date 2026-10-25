<?php

namespace App\Livewire\Product;

use App\Livewire\Shared\Paginates;
use App\Livewire\Shared\Sorts;
use App\Models\Product;
use App\Models\Tax;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * ProductListSelect Livewire Component
 *
 * Displays a selectable list of products for adding to invoices.
 * Supports search, sorting, and product selection with automatic modal handling.
 */
class ProductListSelect extends Component
{
    use Paginates;
    use Sorts;

    /**
     * Search term for filtering products
     */
    #[Url(except: '')]
    public string $search = '';

    #[Url(as: 'sort', except: 'name')]
    public string $orderBy = 'name';

    #[Url(as: 'direction', except: true)]
    public bool $orderAsc = true;

    /**
     * Number of items per page
     */
    #[Url(except: 10)]
    public int $perPage = 10;

    /**
     * Array of selected product IDs to exclude from results
     */
    public array $selectedProductIds = [];

    /**
     * Error message to display to user
     */
    public ?string $errorMessage = null;

    /**
     * Livewire listeners
     *
     * @var array
     */
    protected $listeners = [
        'setSelectedProductIds' => 'handleSetSelectedProductIds',
    ];

    /**
     * Set up component with pagination theme and initialize selected products
     */
    public function mount(): void
    {
        $this->selectedProductIds = [];
    }

    /**
     * Handle incoming selected product IDs from external sources
     *
     * @param  mixed  $params
     */
    public function handleSetSelectedProductIds($params): void
    {
        // If it's an array with 'ids' key, use that (for newer Livewire versions)
        if (is_array($params) && isset($params['ids']) && is_array($params['ids'])) {
            $this->setSelectedProductIds($params['ids']);
        }
        // Otherwise assume the parameter is directly an array of IDs (for older Livewire versions)
        elseif (is_array($params)) {
            $this->setSelectedProductIds($params);
        }
    }

    /**
     * Set the selected product IDs to exclude from results
     */
    public function setSelectedProductIds(array $ids): void
    {
        $this->selectedProductIds = $ids;
    }

    /**
     * Reset pagination when search term is updated
     */
    public function updatingSearch(): void
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

    protected function sortableColumns(): array
    {
        return [
            'name' => 'name',
            'category_id' => 'category_id',
            'price' => 'price',
            'currency' => 'currency',
            'tax_id' => 'tax_id',
        ];
    }

    protected function defaultSort(): string
    {
        return 'name';
    }

    /**
     * Select a product and dispatch events
     */
    public function selectProduct(int $id): bool
    {
        try {
            $product = Product::where('user_id', Auth::id())->findOrFail($id);

            // Manually load tax to ensure its availability
            if ($product->tax_id) {
                $product->load('tax');
            }

            // Ensure all fields are properly formatted
            $price = $product->price ?? 0;

            // Get tax_rate directly from Tax model
            $tax_rate = null;
            if ($product->tax_id) {
                if ($product->tax) {
                    $tax_rate = $product->tax->rate;
                } else {
                    // Try to load tax directly if relation failed
                    try {
                        $tax = Tax::find($product->tax_id);
                        if ($tax) {
                            $tax_rate = $tax->rate;
                        }
                    } catch (\Exception $e) {
                        // Failed to load tax - continue without tax rate
                    }
                }
            }

            $unit = $product->unit ?? '';
            $currency = $product->currency ?? 'CZK';

            // Prepare data to send
            $productData = [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $price,
                'tax_rate' => $tax_rate,
                'unit' => $unit,
                'currency' => $currency,
            ];

            // Dispatch browser event with complete product data
            $this->dispatch('product-selected', productData: $productData);

            // Close the modal automatically
            $this->dispatch('closeModal');

            return true;
        } catch (\Exception $e) {
            $this->errorMessage = 'Error while selecting product.';

            return false;
        }
    }

    /**
     * Render the component with products data
     */
    public function render(): View
    {
        try {
            $searchTerm = $this->search;

            // Explicit preloading of tax rates
            $query = Product::with(['tax'])
                ->where('user_id', Auth::id())
                ->when($searchTerm, function ($query) use ($searchTerm) {
                    $query->where(function ($q) use ($searchTerm) {
                        $q->where(
                            'name',
                            'like',
                            "%{$searchTerm}%",
                        )
                            ->orWhere(
                                'description',
                                'like',
                                "%{$searchTerm}%",
                            );
                    });
                })
                ->orderBy($this->sortColumn(), $this->sortDirection());

            // If we have selected products, exclude them from results
            if (! empty($this->selectedProductIds)) {
                $query->whereNotIn('id', $this->selectedProductIds);
            }

            $products = $query->paginate($this->perPage);

            return view('livewire.product.product-list-select', [
                'products' => $products,
                'hasData' => $products->total() > 0,
            ]);
        } catch (\Exception $e) {
            $this->errorMessage = 'Error while loading products for selection.';

            // Return empty products collection in case of error
            return view('livewire.product.product-list-select', [
                'products' => Product::where('id', 0)->paginate(10), // Empty paginator
                'hasData' => false,
                'errorMessage' => $this->errorMessage,
            ]);
        }
    }
}
