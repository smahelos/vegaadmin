<?php

declare(strict_types=1);

namespace App\Livewire\Party;

use App\Application\Party\Queries\ListLatestBusinesses;
use App\Application\Party\Queries\ListLatestContacts;
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

    public function render(): View
    {
        $business = $this->kind === PartyKind::BUSINESS;
        $this->requireAllowed(
            $business ? ListLatestBusinesses::class : ListLatestContacts::class,
        );
        $owner = $this->userId();
        if ($business) {
            $parties = $this->runUseCase(
                new ListLatestBusinesses($owner),
            );
        } else {
            $parties = $this->runUseCase(
                new ListLatestContacts($owner),
            );
        }

        return view(
            'livewire.party.party-list-latest',
            [
                'parties' => $parties,
                'resource' => $business ? 'businesses' : 'contacts',
            ],
        );
    }
}
