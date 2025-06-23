<?php

namespace App\Livewire;

use App\Models\Client;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

/**
 * ClientListLatest Livewire Component
 * 
 * Displays a paginated list of the latest clients for the authenticated user.
 * Shows the most recently created clients with sorting functionality.
 */
class ClientListLatest extends Component
{   
    use WithPagination;
    
    public bool $orderAsc = false;
    
    public string $orderBy = 'created_at';
    
    public ?string $errorMessage = null; 
    
    /**
     * Sort by given field, toggle direction if same field
     *
     * @param string $field
     */
    public function sortBy(string $field): void
    {
        if ($this->orderBy === $field) {
            $this->orderAsc = !$this->orderAsc;
        } else {
            $this->orderBy = $field;
            $this->orderAsc = true;
        }
    }

    /**
     * Render the component with latest clients data
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function render(): \Illuminate\Contracts\View\View
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
