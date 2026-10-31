<?php

declare(strict_types=1);

namespace App\Livewire\Party;

use App\Application\Invoice\Contracts\InvoiceListingApplicationServiceInterface;
use App\Application\Party\Services\BankAccountApplicationService;
use App\Application\Party\Services\BusinessApplicationService;
use App\Application\Party\Services\CompanyRegistryApplicationService;
use App\Application\Party\Services\ContactApplicationService;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Party\DTO\BankAccountDTO;
use App\Domain\Party\DTO\BusinessDTO;
use App\Domain\Party\DTO\ContactDTO;
use App\Domain\Party\DTO\DocumentAccountDTO;
use App\Domain\Party\DTO\DocumentParties;
use App\Domain\Party\DTO\DocumentPartyDTO;
use App\Domain\Party\DTO\DuplicatePartyDTO;
use App\Domain\Party\DTO\PartySearch;
use App\Domain\Party\Enums\PartySort;
use App\Domain\Party\Exceptions\InvalidAddressException;
use App\Domain\Party\Exceptions\InvalidRegistrationNumberException;
use App\Domain\Party\ValueObjects\AccountOwner;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\Shared\Pagination\DTO\PageRequest;
use App\Http\Requests\Party\BankAccountInput;
use App\Http\Requests\Party\DocumentPartyInput;
use App\Livewire\Party\Forms\BankAccountForm;
use App\Livewire\Party\Forms\PartyDetailsForm;
use App\Livewire\Shared\ActsForUser;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * The parties of the invoice form, inside the form it posts with: the user's business and its account are
 * selected, the customer is picked from the contacts or entered once. A guest enters both parties and the account.
 * The inputs carry the request keys (`business_id`, `customer_name`, …), so the invoice form submits them.
 */
final class DocumentPartyPicker extends Component
{
    use ActsForUser;
    use ProvidesPartyOptions;

    private const CONTACT_RESULTS = 8;

    #[Locked]
    public bool $guest = false;

    #[Locked]
    public ?int $invoiceId = null;

    /** The payment method the account is for; without it the account is always shown. */
    #[Locked]
    public ?int $bankTransferMethodId = null;

    public ?int $businessId = null;

    public ?int $bankAccountId = null;

    public ?int $contactId = null;

    public bool $reloadBusiness = false;

    public bool $saveToContacts = false;

    public string $contactQuery = '';

    public PartyDetailsForm $customer;

    public PartyDetailsForm $supplier;

    public BankAccountForm $account;

    public ?string $customerRegisterMessage = null;

    public ?string $supplierRegisterMessage = null;

    public function mount(
        BusinessApplicationService $businesses,
        BankAccountApplicationService $accounts,
        ContactApplicationService $contacts,
        InvoiceListingApplicationServiceInterface $invoices,
        bool $guest = false,
        ?int $invoiceId = null,
        ?int $businessId = null,
        ?int $contactId = null,
    ): void {
        $this->guest = $guest;
        $this->customer->country = 'CZ';
        $this->supplier->country = 'CZ';

        if (! $guest) {
            $invoiceId === null
                ? $this->startNew(
                    $businesses,
                    $accounts,
                    $contacts,
                    $businessId,
                    $contactId,
                )
                : $this->startFromInvoice($invoices->getById(InvoiceId::fromInt($invoiceId), $this->userId())->parties, $invoiceId);
        }

        $this->fillFromOldInput();
        $this->showRequestErrors();
    }

    public function updatedBusinessId(BankAccountApplicationService $accounts): void
    {
        $this->reloadBusiness = false;
        $this->bankAccountId = $this->businessId === null
            ? null
            : $accounts->findDefaultId($this->userId(), AccountOwner::business(BusinessId::fromInt($this->businessId)))?->value;
    }

    public function updatedCustomerCompanyNumber(): void
    {
        $this->customer->prefillRegistrationCountry();
    }

    public function updatedSupplierCompanyNumber(): void
    {
        $this->supplier->prefillRegistrationCountry();
    }

    /**
     * The invoice keeps the business data it was saved with until the user asks for the current ones.
     */
    public function reloadBusinessData(): void
    {
        $this->reloadBusiness = true;
    }

    public function selectContact(ContactApplicationService $contacts, int $contactId): void
    {
        $contact = $contacts->get($this->userId(), ContactId::fromInt($contactId));
        if ($contact->isArchived()) {
            $this->addError('contactId', __('parties.validation.reference_archived'));

            return;
        }

        $this->useContact($contact);
        $this->customer->fillFromProfile($contact->profile);
    }

    /**
     * Links the entered customer to an existing contact without overwriting what was entered.
     */
    public function linkContact(ContactApplicationService $contacts, int $contactId): void
    {
        $contact = $contacts->get($this->userId(), ContactId::fromInt($contactId));
        $this->useContact($contact);
    }

    public function reloadContact(ContactApplicationService $contacts): void
    {
        if ($this->contactId !== null) {
            $this->customer->fillFromProfile($contacts->get($this->userId(), ContactId::fromInt($this->contactId))->profile);
        }
    }

    public function clearContact(): void
    {
        $this->contactId = null;
    }

    public function lookUpCustomer(CompanyRegistryApplicationService $registry): void
    {
        $this->customerRegisterMessage = $this->customer->lookUpInRegister($registry) ? __('parties.messages.register_filled') : null;
    }

    public function lookUpSupplier(CompanyRegistryApplicationService $registry): void
    {
        $this->supplierRegisterMessage = $this->supplier->lookUpInRegister($registry) ? __('parties.messages.register_filled') : null;
    }

    public function render(
        BusinessApplicationService $businesses,
        BankAccountApplicationService $accounts,
        ContactApplicationService $contacts,
        InvoiceListingApplicationServiceInterface $invoices,
    ): View {
        if ($this->guest) {
            return view('livewire.party.document-party-picker', [
                'countries' => $this->countryOptions(),
                'banks' => $this->bankOptions($this->account->country),
            ]);
        }

        $userId = $this->userId();
        $stored = $this->invoiceId === null
            ? null
            : $invoices->getById(InvoiceId::fromInt($this->invoiceId), $userId)->parties;
        $business = $this->businessId === null ? null : $businesses->get($userId, BusinessId::fromInt($this->businessId));
        $contact = $this->contactId === null ? null : $contacts->get($userId, ContactId::fromInt($this->contactId));
        $businessAccounts = $business === null ? [] : $accounts->listForOwner($userId, AccountOwner::business($business->id));

        return view('livewire.party.document-party-picker', [
            'countries' => $this->countryOptions(),
            'businesses' => $this->businessOptions($businesses->listActive($userId), $business),
            'business' => $business,
            'supplierPreview' => $this->supplierPreview($business, $stored),
            'supplierLogo' => $this->keepsStoredBusiness($stored) ? $stored?->supplierLogo : $business?->logo,
            'businessChanged' => $this->businessChanged($business, $stored),
            'accounts' => $this->accountOptions($businessAccounts, $stored),
            'accountPreview' => $this->accountPreview($businessAccounts, $stored),
            'contact' => $contact,
            'contactSearched' => $this->searchTerm() !== null,
            'contactResults' => $this->contactResults($contacts),
            'existingContact' => $this->existingContact($contacts),
            'canSaveToContacts' => $this->userCan('frontend.can_create_contact'),
        ]);
    }

    private function startNew(
        BusinessApplicationService $businesses,
        BankAccountApplicationService $accounts,
        ContactApplicationService $contacts,
        ?int $businessId,
        ?int $contactId,
    ): void {
        $business = $businessId === null ? $businesses->findDefaultId($this->userId()) : BusinessId::fromInt($businessId);
        if ($business !== null) {
            $this->businessId = $businesses->get($this->userId(), $business)->id->value;
            $this->bankAccountId = $accounts->findDefaultId($this->userId(), AccountOwner::business($business))?->value;
        }

        if ($contactId !== null) {
            $this->selectContact($contacts, $contactId);
        }
    }

    private function startFromInvoice(DocumentParties $stored, int $invoiceId): void
    {
        $this->invoiceId = $invoiceId;
        $this->businessId = $stored->businessId->value;
        $this->bankAccountId = $stored->bankAccountId?->value;
        $this->contactId = $stored->contactId?->value;
        $this->customer->fillFromProfile($stored->customer->toProfile());
    }

    private function useContact(ContactDTO $contact): void
    {
        $this->contactId = $contact->id->value;
        $this->contactQuery = '';
        $this->saveToContacts = false;
        $this->resetErrorBag('contactId');
    }

    /**
     * After a failed validation the invoice form comes back with the posted values.
     */
    private function fillFromOldInput(): void
    {
        if (old('_token') === null) {
            return;
        }

        if (! $this->guest) {
            $this->businessId = $this->oldId('business_id');
            $this->bankAccountId = $this->oldId('bank_account_id');
            $this->contactId = $this->oldId('contact_id');
            $this->reloadBusiness = (bool) old('reload_business');
            $this->saveToContacts = (bool) old('save_customer_to_contacts');
        }

        foreach (DocumentPartyInput::FIELDS as $field) {
            $this->customer->{Str::camel($field)} = (string) old('customer_' . $field, '');
            if ($this->guest) {
                $this->supplier->{Str::camel($field)} = (string) old('supplier_' . $field, '');
            }
        }

        if ($this->guest) {
            foreach (BankAccountInput::FIELDS as $field) {
                $this->account->{Str::camel($field)} = (string) old('account_' . $field, $this->account->{Str::camel($field)});
            }
        }
    }

    private function oldId(string $key): ?int
    {
        $value = old($key);

        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * The invoice request names its fields by the request keys; the component shows them on its own properties.
     */
    private function showRequestErrors(): void
    {
        $errors = session('errors');
        if (! $errors instanceof ViewErrorBag) {
            return;
        }

        foreach ($errors->getBag('default')->messages() as $key => $messages) {
            $property = $this->propertyOf($key);
            if ($property !== null) {
                $this->addError($property, $messages[0]);
            }
        }
    }

    private function propertyOf(string $key): ?string
    {
        foreach ([
            'business_id' => 'businessId',
            'bank_account_id' => 'bankAccountId',
            'contact_id' => 'contactId',
        ] as $requestKey => $property) {
            if ($key === $requestKey) {
                return $property;
            }
        }

        foreach ([
            'customer',
            'supplier',
            'account',
        ] as $form) {
            if (str_starts_with($key, $form . '_')) {
                return $form . '.' . Str::camel(substr($key, strlen($form) + 1));
            }
        }

        return null;
    }

    /**
     * @param  list<BusinessDTO>  $active
     * @return array<int, string>
     */
    private function businessOptions(array $active, ?BusinessDTO $selected): array
    {
        $options = [];
        foreach ($active as $business) {
            $options[$business->id->value] = $business->profile->name;
        }

        if ($selected !== null && $selected->isArchived()) {
            $options[$selected->id->value] = $selected->profile->name . ' (' . __('parties.statuses.archived') . ')';
        }

        return $options;
    }

    /**
     * The business data the invoice will carry: the stored copy until the business changes or is reloaded.
     */
    private function supplierPreview(?BusinessDTO $business, ?DocumentParties $stored): ?DocumentPartyDTO
    {
        if ($this->keepsStoredBusiness($stored)) {
            return $stored?->supplier;
        }

        return $business === null ? null : DocumentPartyDTO::fromProfile($business->profile);
    }

    private function businessChanged(?BusinessDTO $business, ?DocumentParties $stored): bool
    {
        return $business !== null &&
            $stored !== null &&
            $this->keepsStoredBusiness($stored) &&
            (DocumentPartyDTO::fromProfile($business->profile) != $stored->supplier || $business->logo !== null && $stored->supplierLogo === null);
    }

    private function keepsStoredBusiness(?DocumentParties $stored): bool
    {
        return $stored !== null &&
            ! $this->reloadBusiness &&
            $stored->businessId->value === $this->businessId;
    }

    /**
     * Active accounts of the business, and the stored one even when archived since.
     *
     * @param  list<BankAccountDTO>  $accounts
     * @return array<int, string>
     */
    private function accountOptions(array $accounts, ?DocumentParties $stored): array
    {
        $storedId = $stored?->bankAccountId;
        $options = [];
        foreach ($accounts as $account) {
            $isStored = $storedId !== null && $account->id->equals($storedId);
            if ($account->isArchived() && ! $isStored) {
                continue;
            }

            $label = $account->label ?? $account->account->formattedIban();
            $options[$account->id->value] = $account->isArchived()
                ? $label . ' (' . __('parties.statuses.archived') . ')'
                : $label;
        }

        return $options;
    }

    /**
     * The account data the invoice will carry, kept like the business data.
     *
     * @param  list<BankAccountDTO>  $accounts
     */
    private function accountPreview(array $accounts, ?DocumentParties $stored): ?DocumentAccountDTO
    {
        if ($this->bankAccountId === null) {
            return null;
        }

        if ($stored !== null && $this->keepsStoredBusiness($stored) && $stored->bankAccountId?->value === $this->bankAccountId) {
            return $stored->account;
        }

        foreach ($accounts as $account) {
            if ($account->id->value === $this->bankAccountId) {
                return DocumentAccountDTO::fromBankAccount($account);
            }
        }

        return null;
    }

    /**
     * @return list<ContactDTO>
     */
    private function contactResults(ContactApplicationService $contacts): array
    {
        $term = $this->searchTerm();
        if ($term === null) {
            return [];
        }

        return $contacts->search(
            $this->userId(),
            new PartySearch(
                $term,
                false,
                PartySort::NAME,
                true,
            ),
            new PageRequest(1, self::CONTACT_RESULTS),
        )->items();
    }

    /**
     * The contact search starts with the second character.
     */
    private function searchTerm(): ?string
    {
        $term = trim($this->contactQuery);

        return mb_strlen($term) < 2 ? null : $term;
    }

    /**
     * A contact with the Czech company number of the customer about to be saved to the contacts, which the user
     * links instead of creating a duplicate.
     */
    private function existingContact(ContactApplicationService $contacts): ?DuplicatePartyDTO
    {
        if (! $this->saveToContacts || $this->contactId !== null || trim($this->customer->companyNumber) === '') {
            return null;
        }

        try {
            $profile = DocumentPartyInput::party(
                $this->prefixed('customer_', $this->customer->input()),
                'customer',
            )->toProfile();
        } catch (InvalidRegistrationNumberException|InvalidAddressException) {
            return null;
        }

        foreach ($contacts->findDuplicates(
            $this->userId(),
            $profile,
            null,
        ) as $duplicate) {
            if ($duplicate->reason->isBlocking()) {
                return $duplicate;
            }
        }

        return null;
    }

    /**
     * @param  array<string, string>  $input
     * @return array<string, string>
     */
    private function prefixed(string $prefix, array $input): array
    {
        $prefixed = [];
        foreach ($input as $field => $value) {
            $prefixed[$prefix . $field] = $value;
        }

        return $prefixed;
    }
}
