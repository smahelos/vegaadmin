<?php

namespace App\Livewire;

use App\Models\Client;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class ClientList extends Component
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

    protected $paginationTheme = 'tailwind';
    
    public function mount()
    {
        // Set theme for pagination to ensure correct styling
        $this->paginationTheme = 'tailwind';
    }
    
    public function render()
    {
        try {
            $query = Client::where('user_id', Auth::id())
                ->withCount('invoices'); // Add count of invoices for each client
            
            // Handle special sort cases
            if ($this->orderBy === 'invoices') {
                // Sort by the number of invoices
                $query->orderBy('invoices_count', $this->orderAsc ? 'asc' : 'desc');
            } else {
                // Standard sorting for other fields
                $query->orderBy($this->orderBy, $this->orderAsc ? 'asc' : 'desc');
            }
            
            $clients = $query->paginate(10);
            
            return view('livewire.client-list', [
                'clients' => $clients,
                'hasData' => $clients->total() > 0,
            ]);
        } catch (\Exception $e) {
            $this->errorMessage = 'Error while loading clients.';
            
            return view('livewire.client-list', [
                'clients' => collect(),
                'hasData' => false,
                'errorMessage' => $this->errorMessage
            ]);
        }
    }
}
