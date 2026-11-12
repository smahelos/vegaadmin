<?php

declare(strict_types=1);

namespace App\Livewire\Party;

use App\Application\Party\Queries\FindDefaultBusiness;
use App\Application\Party\Queries\ListBusinesses;
use App\Livewire\Shared\ActsForUser;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class BusinessList extends Component
{
    use ActsForUser;
    use ListsParties;

    public function render(): View
    {
        $this->requireAllowed(ListBusinesses::class);
        $owner = $this->userId();

        return view(
            'livewire.party.business-list',
            [
                'businesses' => $this->paginator(
                    $this->runUseCase(
                        new ListBusinesses(
                            $owner,
                            $this->partySearch(),
                            $this->pageRequest(),
                        ),
                    ),
                ),
                'defaultId' => $this->runUseCase(
                    new FindDefaultBusiness($owner),
                ),
            ],
        );
    }
}
