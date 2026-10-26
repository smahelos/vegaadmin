<?php

declare(strict_types=1);

namespace App\Livewire\Party;

use App\Application\Party\Services\ContactApplicationService;
use App\Livewire\Shared\ActsForUser;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class ContactList extends Component
{
    use ActsForUser;
    use ListsParties;

    public function render(ContactApplicationService $contacts): View
    {
        $this->requirePermission('frontend.can_view_contact');

        return view('livewire.party.contact-list', [
            'contacts' => $this->paginator($contacts->search(
                $this->userId(),
                $this->partySearch(),
                $this->pageRequest(),
            )),
        ]);
    }
}
