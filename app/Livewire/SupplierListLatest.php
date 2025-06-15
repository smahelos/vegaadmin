<?php

namespace App\Livewire;

use App\Models\Supplier;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class SupplierListLatest extends Component
{   
    use WithPagination;
    public $orderAsc = false;
    
    public $orderBy = 'created_at';
    
    public $errorMessage; 
    
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
            $query = Supplier::where('user_id', Auth::id());
            $query->orderBy($this->orderBy, $this->orderAsc ? 'asc' : 'desc');

            $suppliers = $query->paginate(5);
            return view('livewire.supplier-list-latest', [
                'suppliers' => $suppliers,
                'hasData' => $suppliers->total() > 0,
                'errorMessage' => $this->errorMessage,
            ]);
        } catch (\Exception $e) {
            $this->errorMessage = 'Error while loading latest suppliers.';
            
            return view('livewire.supplier-list-latest', [
                'suppliers' => collect(),
                'hasData' => false,
                'errorMessage' => $this->errorMessage
            ]);
        }
    }
}
