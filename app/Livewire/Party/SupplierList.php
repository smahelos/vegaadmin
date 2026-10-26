<?php

namespace App\Livewire\Party;

use App\Livewire\Shared\Paginates;
use App\Livewire\Shared\Sorts;
use App\Models\Supplier;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Url;
use Livewire\Component;
use Throwable;

class SupplierList extends Component
{
    use Paginates;
    use Sorts;

    #[Url]
    public string $search = '';

    #[Url(as: 'sort')]
    public string $orderBy = 'created_at';

    #[Url(as: 'direction')]
    public bool $orderAsc = false;

    public ?string $errorMessage = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    /** Reset all filters and pagination */
    public function resetFilters(): void
    {
        $this->search = '';
        $this->resetPage();
    }

    protected function sortableColumns(): array
    {
        return [
            'created_at' => 'created_at',
            'name' => 'name',
            'email' => 'email',
            'phone' => 'phone',
            'ico' => 'ico',
            'is_default' => 'is_default',
            'invoices' => 'invoices_count',
        ];
    }

    protected function defaultSort(): string
    {
        return 'created_at';
    }

    public function render(): View
    {
        try {
            $query = Supplier::where('user_id', Auth::id())
                ->withCount('invoices');

            if ($this->search) {
                $query->where(function ($q): void {
                    $q->where(
                        'name',
                        'like',
                        "%{$this->search}%",
                    )
                        ->orWhere(
                            'email',
                            'like',
                            "%{$this->search}%",
                        )
                        ->orWhere(
                            'ico',
                            'like',
                            "%{$this->search}%",
                        );
                });
            }

            $query->orderBy($this->sortColumn(), $this->sortDirection());

            $suppliers = $query->paginate(10);

            return view('livewire.party.supplier-list', [
                'suppliers' => $suppliers,
                'hasData' => $suppliers->total() > 0,
            ]);
        } catch (Throwable $e) {
            Log::error('Loading suppliers failed', ['exception' => $e]);
            $this->errorMessage = __('suppliers.messages.error_loading');

            return view('livewire.party.supplier-list', [
                'suppliers' => collect(),
                'hasData' => false,
                'errorMessage' => $this->errorMessage,
            ]);
        }
    }
}
