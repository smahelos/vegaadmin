<?php

namespace App\Livewire\Party;

use App\Livewire\Shared\Paginates;
use App\Models\Supplier;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Throwable;

class SupplierListLatest extends Component
{
    use Paginates;

    public ?string $errorMessage = null;

    public function render(): View
    {
        try {
            $query = Supplier::where('user_id', Auth::id())->latest();
            $suppliers = $query->paginate(5);

            return view('livewire.party.supplier-list-latest', [
                'suppliers' => $suppliers,
                'hasData' => $suppliers->total() > 0,
                'errorMessage' => $this->errorMessage,
            ]);
        } catch (Throwable $e) {
            Log::error('Loading latest suppliers failed', ['exception' => $e]);
            $this->errorMessage = __('suppliers.messages.error_loading');

            return view('livewire.party.supplier-list-latest', [
                'suppliers' => collect(),
                'hasData' => false,
                'errorMessage' => $this->errorMessage,
            ]);
        }
    }
}
