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
    public $selectedProductIds = [];

    protected $queryString = [
        'search' => ['except' => ''],
        'sortField' => ['except' => 'name'],
        'sortDirection' => ['except' => 'asc'],
        'perPage' => ['except' => 10],
    ];
    
    protected $listeners = [
        'setSelectedProductIds' => 'handleSetSelectedProductIds'
    ];
    
    public function mount()
    {
        $this->selectedProductIds = [];
    }
    
    public function handleSetSelectedProductIds($params)
    {
        // Pokud je to array s klíčem 'ids', použijeme to (pro novější verzi Livewire)
        if (is_array($params) && isset($params['ids']) && is_array($params['ids'])) {
            $this->setSelectedProductIds($params['ids']);
        } 
        // Jinak předpokládáme, že parametr je přímo pole ID (pro starší verzi Livewire)
        elseif (is_array($params)) {
            $this->setSelectedProductIds($params);
        }
    }
    
    public function setSelectedProductIds(array $ids)
    {
        // Implementace metody, která nastavuje vybrané produkty
        $this->selectedProductIds = $ids;
    }

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
            // Explicitní načtení produktu včetně relace tax
            $product = Product::findOrFail($id);
            
            // Manuálně načteme tax, abychom zajistili jeho dostupnost
            if ($product->tax_id) {
                $product->load('tax');
            }
            
            // Ensure all fields are properly formatted
            $price = $product->price ?? 0;
            
            // Získání tax_rate přímo z modelu Tax
            $tax_rate = null;
            if ($product->tax_id) {
                if ($product->tax) {
                    $tax_rate = $product->tax->rate;
                } else {
                    // Zkusíme načíst daň přímo, pokud relace selhala
                    try {
                        $tax = \App\Models\Tax::find($product->tax_id);
                        if ($tax) {
                            $tax_rate = $tax->rate;
                        }
                    } catch (\Exception $e) {
                        // Failed to load tax
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
            
            // Dispatch browser event with complete product data
            $this->dispatch('product-selected', productData: $productData);
            
            // Close the modal automatically
            $this->dispatch('closeModal');
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function render()
    {
        try {
            $user = Auth::user();
            
            // Explicitní předběžné načtení daňových sazeb
            $query = Product::with(['tax'])
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

            // Pokud máme vybrané produkty, vyloučíme je z výsledků
            if (!empty($this->selectedProductIds)) {
                $query->whereNotIn('id', $this->selectedProductIds);
            }
            
            $products = $query->paginate($this->perPage);

            return view('livewire.product-list-select', [
                'products' => $products
            ]);
        } catch (\Exception $e) {
            // Return empty products collection in case of error
            return view('livewire.product-list-select', [
                'products' => collect([])
            ]);
        }
    }
}
