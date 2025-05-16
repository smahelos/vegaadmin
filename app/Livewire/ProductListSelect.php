<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;

class ProductListSelect extends Component
{
    use WithPagination;

    public $search = '';
    public $sortField = 'name';
    public $sortDirection = 'asc';
    public $perPage = 10;

    protected $queryString = [
        'search' => ['except' => ''],
        'sortField' => ['except' => 'name'],
        'sortDirection' => ['except' => 'asc'],
        'perPage' => ['except' => 10],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }
        $this->sortField = $field;
    }

    public function selectProduct($id)
    {
        try {
            \Log::info("ProductListSelect::selectProduct method called", ['product_id' => $id]);

            // Explicitní načtení produktu včetně relace tax
            $product = Product::findOrFail($id);
            
            // Manuálně načteme tax, abychom zajistili jeho dostupnost
            if ($product->tax_id) {
                $product->load('tax');
            }
            
            // Podrobné logování produktu pro diagnostiku
            \Log::channel('daily')->info('Product selected details', [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'tax_id' => $product->tax_id,
                'tax_loaded' => $product->relationLoaded('tax'),
                'tax_exists' => $product->tax ? true : false
            ]);
            
            // Ensure all fields are properly formatted
            $price = $product->price ?? 0;
            
            // Získání tax_rate přímo z modelu Tax
            $tax_rate = null;
            if ($product->tax_id) {
                if ($product->tax) {
                    $tax_rate = $product->tax->rate;
                    \Log::channel('daily')->info('Tax rate found', [
                        'tax_id' => $product->tax_id,
                        'tax_rate' => $tax_rate
                    ]);
                } else {
                    // Zkusíme načíst daň přímo, pokud relace selhala
                    try {
                        $tax = \App\Models\Tax::find($product->tax_id);
                        if ($tax) {
                            $tax_rate = $tax->rate;
                            \Log::channel('daily')->info('Tax rate loaded directly', [
                                'tax_id' => $product->tax_id,
                                'tax_rate' => $tax_rate
                            ]);
                        }
                    } catch (\Exception $e) {
                        \Log::channel('daily')->error('Failed to load tax', [
                            'tax_id' => $product->tax_id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
            
            $unit = $product->unit ?? '';
            $currency = $product->currency ?? 'CZK';
            
            // Příprava dat k odeslání
            $productData = [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $price,
                'tax_rate' => $tax_rate,
                'unit' => $unit,
                'currency' => $currency,
            ];
            
            // Log všech odesílaných dat
            \Log::channel('daily')->info('Dispatching product data', $productData);
            
            // Dispatch browser event with complete product data
            $this->dispatch('product-selected', productData: $productData);
            
            // Close the modal automatically
            $this->dispatch('closeModal');
            
            return true;
        } catch (\Exception $e) {
            \Log::channel('daily')->error('Error in selectProduct', [
                'product_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return false;
        }
    }

    public function render()
    {
        try {
            $user = Auth::user();
            
            // Explicitní předběžné načtení daňových sazeb
            $query = Product::with('tax')
                ->when($user, function ($query) use ($user) {
                    return $query->where('user_id', $user->id);
                })
                ->when($this->search, function ($query) {
                    $query->where(function($query) {
                        $query->where('name', 'like', '%' . $this->search . '%')
                            ->orWhere('code', 'like', '%' . $this->search . '%')
                            ->orWhere('description', 'like', '%' . $this->search . '%');
                    });
                })
                ->orderBy($this->sortField, $this->sortDirection);

            $products = $query->paginate($this->perPage);
            
            // Log prvních pár produktů pro kontrolu načtení tax
            if ($products->count() > 0) {
                $sampleProduct = $products->first();
                \Log::channel('daily')->info('Sample product from listing', [
                    'product_id' => $sampleProduct->id,
                    'product_name' => $sampleProduct->name,
                    'tax_id' => $sampleProduct->tax_id,
                    'tax_loaded' => $sampleProduct->relationLoaded('tax'),
                    'has_tax' => $sampleProduct->tax ? true : false,
                    'tax_rate' => $sampleProduct->tax ? $sampleProduct->tax->rate : null
                ]);
            }

            return view('livewire.product-list-select', [
                'products' => $products
            ]);
        } catch (\Exception $e) {
            \Log::channel('daily')->error('Error in render method', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return empty products collection in case of error
            return view('livewire.product-list-select', [
                'products' => collect([])
            ]);
        }
    }
}
