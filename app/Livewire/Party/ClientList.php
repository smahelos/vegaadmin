<?php

namespace App\Livewire\Party;

use App\Models\Client;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

/**
 * ClientList Livewire Component
 * 
 * Handles displaying and filtering a paginated list of clients for the authenticated user.
 * Provides search, sorting, and pagination functionality.
 */
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
    
    /** @var string */
    protected $paginationTheme = 'tailwind';
    
    /** Reset pagination when search term is updated */
    public function updatingSearch(): void
    {
        $this->resetPage();
    }
    
    /** Reset pagination when status filter is updated */
    public function updatingStatus(): void
    {
        $this->resetPage();
    }
    
    /** Reset all filters and pagination */
    public function resetFilters(): void
    {
        $this->search = '';
        $this->status = '';
        $this->resetPage();
    }
    
    /**
     * Sort by given field, toggle direction if same field
     *
     * @param string $field
     */
    public function sortBy($field): void
    {
        if ($this->orderBy === $field) {
            $this->orderAsc = !$this->orderAsc;
        } else {
            $this->orderBy = $field;
            $this->orderAsc = true;
        }
    }

    /** Component initialization */
    public function mount(): void
    {
        $this->paginationTheme = 'tailwind';
    }
    
    /**
     * Render the component with clients data
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function render(): \Illuminate\Contracts\View\View
    {
        try {
            $query = Client::where('user_id', Auth::id())
                ->withCount('invoices');
            
            if (!empty($this->search)) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%')
                      ->orWhere('street', 'like', '%' . $this->search . '%')
                      ->orWhere('city', 'like', '%' . $this->search . '%');
                });
            }
            
            if ($this->orderBy === 'invoices') {
                $query->orderBy('invoices_count', $this->orderAsc ? 'asc' : 'desc');
            } else {
                $query->orderBy($this->orderBy, $this->orderAsc ? 'asc' : 'desc');
            }
            
            $clients = $query->paginate(10);
            
            return view('livewire.party.client-list', [
                'clients' => $clients,
                'hasData' => $clients->total() > 0,
            ]);
        } catch (\Exception $e) {
            $this->errorMessage = 'Error while loading clients.';
            
            return view('livewire.party.client-list', [
                'clients' => collect(),
                'hasData' => false,
                'errorMessage' => $this->errorMessage
            ]);
        }
    }
}
