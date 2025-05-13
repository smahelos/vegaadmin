<?php

namespace App\Livewire;

use App\Models\Invoice;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;

class InvoiceListRecent extends Component
{
    use WithPagination;
    
    #[Url(as: 'sort')]
    public $orderBy = 'created_at';
    
    #[Url(as: 'direction')]
    public $orderAsc = false;
    
    public $errorMessage = null;
    
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
                
            $query->orderBy($this->orderBy, $this->orderAsc ? 'asc' : 'desc');
            
            $invoices = $query->paginate(5);
            
            return view('livewire.invoice-list-recent', [
                'invoices' => $invoices,
                'hasData' => $invoices->total() > 0,
                'errorMessage' => $this->errorMessage,
            ]);
        } catch (\Exception $e) {
            Log::error('Error while loading recent invoices list: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            $this->errorMessage = 'Error while loading recent invoices.';
            
            return view('livewire.invoice-list-recent', [
                'invoices' => Invoice::where('id', 0)->paginate(10), // Empty paginator
                'hasData' => false,
                'errorMessage' => $this->errorMessage
            ]);
        }
    }
}
