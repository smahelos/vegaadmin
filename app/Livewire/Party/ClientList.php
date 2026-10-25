<?php

namespace App\Livewire\Party;

use App\Livewire\Shared\Paginates;
use App\Livewire\Shared\Sorts;
use App\Models\Client;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Url;
use Livewire\Component;
use Throwable;

/**
 * ClientList Livewire Component
 *
 * Handles displaying and filtering a paginated list of clients for the authenticated user.
 * Provides search, sorting, and pagination functionality.
 */
class ClientList extends Component
{
    use Paginates;
    use Sorts;

    #[Url]
    public $search = '';

    #[Url]
    public $status = '';

    #[Url(as: 'sort')]
    public string $orderBy = 'created_at';

    #[Url(as: 'direction')]
    public bool $orderAsc = false;

    #[Url(keep: true)]
    public $page = 1;

    public $errorMessage = null;

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

    /**
     * Render the component with clients data
     */
    public function render(): View
    {
        try {
            $query = Client::where('user_id', Auth::id())
                ->withCount('invoices');

            if (! empty($this->search)) {
                $query->where(function ($q): void {
                    $q->where(
                        'name',
                        'like',
                        '%' . $this->search . '%',
                    )
                        ->orWhere(
                            'email',
                            'like',
                            '%' . $this->search . '%',
                        )
                        ->orWhere(
                            'street',
                            'like',
                            '%' . $this->search . '%',
                        )
                        ->orWhere(
                            'city',
                            'like',
                            '%' . $this->search . '%',
                        );
                });
            }

            $query->orderBy($this->sortColumn(), $this->sortDirection());

            $clients = $query->paginate(10);

            return view('livewire.party.client-list', [
                'clients' => $clients,
                'hasData' => $clients->total() > 0,
            ]);
        } catch (Throwable $e) {
            Log::error('Loading clients failed', ['exception' => $e]);
            $this->errorMessage = __('clients.messages.error_loading');

            return view('livewire.party.client-list', [
                'clients' => collect(),
                'hasData' => false,
                'errorMessage' => $this->errorMessage,
            ]);
        }
    }
}
