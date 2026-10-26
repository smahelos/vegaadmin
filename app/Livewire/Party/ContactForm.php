<?php

declare(strict_types=1);

namespace App\Livewire\Party;

use App\Application\Party\Services\ContactApplicationService;
use App\Domain\Party\DTO\ContactWriteData;
use App\Domain\Party\DTO\PartyProfile;
use App\Domain\Party\Exceptions\DuplicateCompanyNumberException;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\User\Exceptions\EntityLimitExceededException;
use App\Livewire\Party\Forms\PartyDetailsForm;
use App\Livewire\Shared\ActsForUser;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Creates or edits a contact of the user's address book; its bank accounts are managed on the contact detail.
 */
final class ContactForm extends Component
{
    use ActsForUser;
    use ChecksPartyDuplicates;
    use EditsPartyDetails;
    use ProvidesPartyOptions;

    public PartyDetailsForm $party;

    #[Locked]
    public ?int $contactId = null;

    public string $contactPerson = '';

    public ?string $limitMessage = null;

    public function mount(ContactApplicationService $contacts, ?int $contactId = null): void
    {
        if ($contactId === null) {
            $this->requirePermission('frontend.can_create_contact');
            $this->party->country = 'CZ';

            return;
        }

        $this->requirePermission('frontend.can_update_contact');
        $contact = $contacts->get($this->userId(), ContactId::fromInt($contactId));

        $this->contactId = $contactId;
        $this->party->fillFromProfile($contact->profile);
        $this->contactPerson = $contact->contactPerson ?? '';
    }

    public function save(ContactApplicationService $contacts): void
    {
        $creating = $this->contactId === null;
        $this->requirePermission($creating ? 'frontend.can_create_contact' : 'frontend.can_update_contact');
        $this->limitMessage = null;

        $this->validate($this->rules());
        $this->party->validate();

        $profile = $this->party->profile();
        if (! $this->passesDuplicateCheck($profile)) {
            return;
        }

        $contactPerson = trim($this->contactPerson);
        $data = new ContactWriteData($profile, $contactPerson === '' ? null : $contactPerson);

        try {
            $contact = $creating
                ? $contacts->create($this->userId(), $data)
                : $contacts->update(
                    $this->userId(),
                    ContactId::fromInt($this->contactId),
                    $data,
                );
        } catch (EntityLimitExceededException) {
            $this->limitMessage = __('contacts.messages.limit_exceeded');

            return;
        } catch (DuplicateCompanyNumberException $e) {
            $this->showBlockingDuplicate($e->existing);

            return;
        }

        session()->flash('success', __($creating ? 'contacts.messages.created' : 'contacts.messages.updated'));
        $this->redirectRoute('frontend.contacts.show', [
            'locale' => app()->getLocale(),
            'contact' => $contact->id->value,
        ]);
    }

    public function render(): View
    {
        return view('livewire.party.contact-form', [
            'countries' => $this->countryOptions(),
        ]);
    }

    /**
     * @return array<string, list<string>>
     */
    protected function rules(): array
    {
        return [
            'contactPerson' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return ['contactPerson' => __('contacts.fields.contact_person')];
    }

    protected function findDuplicates(PartyProfile $profile): array
    {
        return app(ContactApplicationService::class)->findDuplicates(
            $this->userId(),
            $profile,
            $this->contactId === null ? null : ContactId::fromInt($this->contactId),
        );
    }

    protected function duplicateUrl(BusinessId|ContactId $id): string
    {
        return route('frontend.contacts.show', [
            'locale' => app()->getLocale(),
            'contact' => $id->value,
        ]);
    }
}
