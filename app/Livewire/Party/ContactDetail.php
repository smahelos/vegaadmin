<?php

declare(strict_types=1);

namespace App\Livewire\Party;

use App\Application\Party\Services\ContactApplicationService;
use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\User\Exceptions\EntityLimitExceededException;
use App\Livewire\Shared\ActsForUser;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * The detail of a contact with archiving and restoring.
 */
final class ContactDetail extends Component
{
    use ActsForUser;
    use ProvidesPartyOptions;

    #[Locked]
    public int $contactId;

    public ?string $errorMessage = null;

    public ?string $successMessage = null;

    public function mount(ContactApplicationService $contacts, int $contactId): void
    {
        $this->requirePermission('frontend.can_view_contact');
        $contacts->get($this->userId(), ContactId::fromInt($contactId));
        $this->contactId = $contactId;
    }

    public function archive(ContactApplicationService $contacts): void
    {
        $this->requirePermission('frontend.can_archive_contact');
        $contacts->archive($this->userId(), $this->currentId());
        $this->successMessage = __('contacts.messages.archived');
    }

    public function restore(ContactApplicationService $contacts): void
    {
        $this->requirePermission('frontend.can_restore_contact');
        $this->errorMessage = null;
        $this->successMessage = null;

        try {
            $contacts->restore($this->userId(), $this->currentId());
        } catch (EntityLimitExceededException) {
            $this->errorMessage = __('contacts.messages.limit_exceeded');

            return;
        }

        $this->successMessage = __('contacts.messages.restored');
    }

    public function render(ContactApplicationService $contacts): View
    {
        return view('livewire.party.contact-detail', [
            'contact' => $contacts->get($this->userId(), $this->currentId()),
            'countries' => $this->countryOptions(),
        ]);
    }

    private function currentId(): ContactId
    {
        return ContactId::fromInt($this->contactId);
    }
}
