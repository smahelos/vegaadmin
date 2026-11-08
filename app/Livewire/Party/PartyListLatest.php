<?php

declare(strict_types=1);

namespace App\Livewire\Party;

use App\Application\Party\Services\LatestPartiesApplicationService;
use App\Domain\Party\Enums\PartyKind;
use App\Livewire\Shared\ActsForUser;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

final class PartyListLatest extends Component
{
    use ActsForUser;

    #[Locked]
    public PartyKind $kind;

    public function mount(string $partyType): void
    {
        $this->kind = PartyKind::from($partyType);
    }

    public function render(LatestPartiesApplicationService $parties): View
    {
        $this->requirePermission(
            $this->kind === PartyKind::BUSINESS ? 'frontend.can_view_business' : 'frontend.can_view_contact',
        );

        return view(
            'livewire.party.party-list-latest',
            [
                'parties' => $parties->listLatest($this->userId(), $this->kind),
                'resource' => $this->kind === PartyKind::BUSINESS ? 'businesses' : 'contacts',
            ],
        );
    }
}
