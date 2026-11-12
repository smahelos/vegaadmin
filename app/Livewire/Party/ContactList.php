<?php

declare(strict_types=1);

namespace App\Livewire\Party;

use App\Application\Party\Queries\ListContacts;
use App\Livewire\Shared\ActsForUser;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class ContactList extends Component
{
    use ActsForUser;
    use ListsParties;

    public function render(): View
    {
        $this->requireAllowed(ListContacts::class);

        return view(
            'livewire.party.contact-list',
            [
                'contacts' => $this->paginator(
                    $this->runUseCase(
                        new ListContacts(
                            $this->userId(),
                            $this->partySearch(),
                            $this->pageRequest(),
                        ),
                    ),
                ),
            ],
        );
    }
}
