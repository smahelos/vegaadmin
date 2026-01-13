<?php

namespace App\Livewire\Party;

use App\Models\Supplier;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;

class SupplierListLatest extends Component
{
    use WithPagination;

    public bool $orderAsc = false;
    public string $orderBy = 'created_at';
    public ?string $errorMessage = null;
    protected $paginationTheme = 'tailwind';

    /**
     * Sort by given field, toggle direction if same field
     */
    public function sortBy(string $field): void
    {
        if ($this->orderBy === $field) {
            $this->orderAsc = !$this->orderAsc;
        } else {
            $this->orderBy = $field;
            $this->orderAsc = true;
        }
        // Reset to first page when sorting changes
        $this->resetPage();
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        try {
            $query = Supplier::where('user_id', Auth::id());
            $query->orderBy($this->orderBy, $this->orderAsc ? 'asc' : 'desc');
            $suppliers = $query->paginate(5);

            return view('livewire.party.supplier-list-latest', [
                'suppliers' => $suppliers,
                'hasData' => $suppliers->total() > 0,
                'errorMessage' => $this->errorMessage,
            ]);
        } catch (\Exception $e) {
            $this->errorMessage = 'Error while loading suppliers.';
            return view('livewire.party.supplier-list-latest', [
                'suppliers' => collect(),
                'hasData' => false,
                'errorMessage' => $this->errorMessage,
            ]);
        }
    }
}
