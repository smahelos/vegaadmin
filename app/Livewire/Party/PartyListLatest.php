<?php

declare(strict_types=1);

namespace App\Livewire\Party;

use App\Application\Party\Services\LatestPartiesApplicationService;
use App\Domain\User\Enums\EntityType;
use App\Livewire\Shared\ActsForUser;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

final class PartyListLatest extends Component
{
    use ActsForUser;

    #[Locked]
    public EntityType $kind;

    public function mount(string $partyType): void
    {
        $this->kind = EntityType::from($partyType);
    }

    public function render(LatestPartiesApplicationService $parties): View
    {
        $this->requirePermission($this->kind === EntityType::BUSINESS ? 'frontend.can_view_business' : 'frontend.can_view_contact');

        return view('livewire.party.party-list-latest', [
            'parties' => $parties->listLatest($this->userId(), $this->kind),
            'resource' => $this->kind === EntityType::BUSINESS ? 'businesses' : 'contacts',
        ]);
    }
}
