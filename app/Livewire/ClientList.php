<?php

namespace App\Livewire;

use App\Models\Client;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Log;

class ClientList extends Component
{
    use WithPagination;
    
    public $search = '';
    public $errorMessage = null;
    
    public function render()
    {
        try {
            $query = Client::query();
            
            if ($this->search) {
                $query->where(function($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                      ->orWhere('email', 'like', "%{$this->search}%")
                      ->orWhere('ico', 'like', "%{$this->search}%");
                });
            }
            
            $clients = $query->latest()->paginate(10);
            
            return view('livewire.client-list', [
                'clients' => $clients,
                'hasData' => $clients->total() > 0,
            ]);
        } catch (\Exception $e) {
            Log::error('Error while loading clients: ' . $e->getMessage());
            $this->errorMessage = 'Error while loading clients.';
            
            return view('livewire.client-list', [
                'clients' => collect()->paginate(10),
                'hasData' => false,
                'errorMessage' => $this->errorMessage
            ]);
        }
    }
    
    public function updatingSearch()
    {
        $this->resetPage();
    }
}
