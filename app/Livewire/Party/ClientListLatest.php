<?php

namespace App\Livewire\Party;

use App\Livewire\Shared\Paginates;
use App\Models\Client;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Throwable;

/**
 * ClientListLatest Livewire Component
 *
 * Displays a paginated list of the latest clients for the authenticated user.
 * Shows the most recently created clients.
 */
class ClientListLatest extends Component
{
    use Paginates;

    public ?string $errorMessage = null;

    /**
     * Render the component with latest clients data
     */
    public function render(): View
    {
        try {
            $query = Client::where('user_id', Auth::id())->latest();

            $clients = $query->paginate(5);

            return view('livewire.party.client-list-latest', [
                'clients' => $clients,
                'hasData' => $clients->total() > 0,
                'errorMessage' => $this->errorMessage,
            ]);
        } catch (Throwable $e) {
            Log::error('Loading latest clients failed', ['exception' => $e]);
            $this->errorMessage = __('clients.messages.error_loading');

            return view('livewire.party.client-list-latest', [
                'clients' => collect(),
                'hasData' => false,
                'errorMessage' => $this->errorMessage,
            ]);
        }
    }
}
