<?php

namespace App\Livewire;

use App\Models\Client;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class ClientListLatest extends Component
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
            $query = Client::where('user_id', Auth::id());
            $query->orderBy($this->orderBy, $this->orderAsc ? 'asc' : 'desc');

            $clients = $query->paginate(5);
            return view('livewire.client-list-latest', [
                'clients' => $clients,
                'hasData' => $clients->total() > 0,
                'errorMessage' => $this->errorMessage,
            ]);
        } catch (\Exception $e) {
            $this->errorMessage = 'Error while loading latest clients.';
            
            return view('livewire.client-list-latest', [
                'clients' => collect(),
                'hasData' => false,
                'errorMessage' => $this->errorMessage
            ]);
        }
    }
}
