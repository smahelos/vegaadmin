<?php

declare(strict_types=1);

namespace App\Livewire\Party;

use App\Application\Party\Commands\CreateContact;
use App\Application\Party\Commands\UpdateContact;
use App\Application\Party\Queries\FindContactDuplicates;
use App\Application\Party\Queries\GetContactForEdit;
use App\Application\Party\Services\PartyLogoApplicationService;
use App\Domain\Party\DTO\ContactWriteData;
use App\Domain\Party\DTO\PartyProfile;
use App\Domain\Party\Exceptions\DuplicateCompanyNumberException;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\User\Exceptions\EntityLimitExceededException;
use App\Livewire\Party\Forms\PartyDetailsForm;
use App\Livewire\Shared\ActsForUser;
use App\Livewire\Shared\ThrottlesCreation;
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
    use EditsPartyLogo;
    use ProvidesPartyOptions;
    use ThrottlesCreation;

    public PartyDetailsForm $party;

    #[Locked]
    public ?int $contactId = null;

    public string $contactPerson = '';

    public ?string $errorMessage = null;

    public ?string $limitMessage = null;

    public function mount(?int $contactId = null): void
    {
        if ($contactId === null) {
            $this->requireAllowed(CreateContact::class);
            $this->party->country = 'CZ';

            return;
        }

        $this->requireAllowed(GetContactForEdit::class);
        $contact = $this->runUseCase(
            new GetContactForEdit(
                $this->userId(),
                ContactId::fromInt($contactId),
            ),
        );

        $this->contactId = $contactId;
        $this->party->fillFromProfile($contact->profile);
        $this->contactPerson = $contact->contactPerson ?? '';
        $this->currentLogo = $contact->logo;
    }

    public function save(PartyLogoApplicationService $logos): void
    {
        $creating = $this->contactId === null;
        $this->requireAllowed($creating ? CreateContact::class : UpdateContact::class);
        $this->errorMessage = null;
        $this->limitMessage = null;

        $this->validate($this->rules());
        $this->party->validate();

        $profile = $this->party->profile();
        if (!$this->passesDuplicateCheck($profile)) {
            return;
        }

        if ($creating) {
            $this->errorMessage = $this->creationThrottled();
            if ($this->errorMessage !== null) {
                return;
            }
        }

        $contactPerson = trim($this->contactPerson);
        $logo = $this->logoPath($logos);
        $data = new ContactWriteData(
            $profile,
            $contactPerson === '' ? null : $contactPerson,
            $logo,
        );

        $saved = false;
        try {
            if ($creating) {
                $contact = $this->runUseCase(
                    new CreateContact($this->userId(), $data),
                );
            } else {
                $contact = $this->runUseCase(
                    new UpdateContact(
                        $this->userId(),
                        ContactId::fromInt($this->contactId),
                        $data,
                    ),
                );
            }
            $saved = true;
        } catch (EntityLimitExceededException $e) {
            $this->limitMessage = __('contacts.messages.limit_exceeded', ['limit' => $e->limit]);

            return;
        } catch (DuplicateCompanyNumberException $e) {
            $this->showBlockingDuplicate($e->existing);

            return;
        } finally {
            if (!$saved) {
                $this->discardNewLogo($logos, $logo);
            }
        }

        if ($this->currentLogo !== null && $this->currentLogo !== $logo) {
            $logos->delete($this->currentLogo);
        }

        $session = session();
        $session->flash(
            'success',
            __(
                $creating ? 'contacts.messages.created' : 'contacts.messages.updated',
            ),
        );
        $application = app();
        $this->redirectRoute(
            'frontend.contacts.show',
            [
                'locale' => $application->getLocale(),
                'contact' => $contact->id->value,
            ],
        );
    }

    public function render(): View
    {
        return view(
            'livewire.party.contact-form',
            [
                'countries' => $this->countryOptions(),
                'cancelHref' => $this->cancelHref(),
            ],
        );
    }

    /**
     * @return array<string, list<string>>
     */
    protected function rules(): array
    {
        return [
            'logo' => [
                'nullable',
                'image',
                'mimes:png,jpg,jpeg,webp',
                'max:2048',
            ],
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
        return ['contactPerson' => __('contacts.fields.contact_person'), 'logo' => __('parties.fields.logo')];
    }

    protected function findDuplicates(PartyProfile $profile): array
    {
        return $this->runUseCase(
            new FindContactDuplicates(
                $this->userId(),
                $profile,
                $this->contactId === null ? null : ContactId::fromInt($this->contactId),
            ),
        );
    }

    protected function duplicateUrl(BusinessId|ContactId $id): string
    {
        $application = app();

        return route(
            'frontend.contacts.show',
            [
                'locale' => $application->getLocale(),
                'contact' => $id->value,
            ],
        );
    }

    private function cancelHref(): string
    {
        $application = app();
        $locale = $application->getLocale();

        if ($this->contactId === null) {
            return route('frontend.contacts.index', ['locale' => $locale]);
        }

        return route('frontend.contacts.show', ['locale' => $locale, 'contact' => $this->contactId]);
    }
}
