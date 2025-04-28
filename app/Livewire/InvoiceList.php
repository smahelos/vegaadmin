<?php

namespace App\Livewire;

use App\Models\Invoice;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Log;

class InvoiceList extends Component
{
    use WithPagination;
    
    public $search = '';
    public $status = '';
    public $orderBy = 'created_at';
    public $orderAsc = false;
    public $errorMessage = null;
    
    public function resetFilters()
    {
        $this->search = '';
        $this->status = '';
        $this->resetPage();
    }
    
    public function render()
    {
        try {
            $query = Invoice::query();
            
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
            
            $query->orderBy($this->orderBy, $this->orderAsc ? 'asc' : 'desc');
            
            $invoices = $query->with('client')->paginate(10);
            
            return view('livewire.invoice-list', [
                'invoices' => $invoices,
                'hasData' => $invoices->total() > 0,
            ]);
        } catch (\Exception $e) {
            Log::error('Chyba při načítání seznamu faktur: ' . $e->getMessage());
            $this->errorMessage = 'Nastala chyba při načítání faktur.';
            
            return view('livewire.invoice-list', [
                'invoices' => collect()->paginate(10),
                'hasData' => false,
                'errorMessage' => $this->errorMessage
            ]);
        }
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
    
    public function updatingSearch()
    {
        $this->resetPage();
    }
}