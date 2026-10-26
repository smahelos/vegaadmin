<?php

declare(strict_types=1);

namespace App\Livewire\Party;

use App\Application\Party\Services\BusinessApplicationService;
use App\Livewire\Shared\ActsForUser;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class BusinessList extends Component
{
    use ActsForUser;
    use ListsParties;

    public function render(BusinessApplicationService $businesses): View
    {
        $this->requirePermission('frontend.can_view_business');
        $userId = $this->userId();

        return view('livewire.party.business-list', [
            'businesses' => $this->paginator($businesses->search(
                $userId,
                $this->partySearch(),
                $this->pageRequest(),
            )),
            'defaultId' => $businesses->findDefaultId($userId),
        ]);
    }
}
