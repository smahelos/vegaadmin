<?php

declare(strict_types=1);

namespace App\Application\Invoice\Services;

use App\Application\Invoice\DTO\InvoicePartySelection;
use App\Application\Party\Services\BankAccountApplicationService;
use App\Application\Party\Services\BusinessApplicationService;
use App\Application\Party\Services\ContactApplicationService;
use App\Domain\Party\DTO\ContactWriteData;
use App\Domain\Party\DTO\DocumentAccountDTO;
use App\Domain\Party\DTO\DocumentParties;
use App\Domain\Party\DTO\DocumentPartyDTO;
use App\Domain\Party\Exceptions\ArchivedReferenceSelectedException;
use App\Domain\Party\Exceptions\BankAccountNotFoundException;
use App\Domain\Party\Exceptions\BusinessNotFoundException;
use App\Domain\Party\Exceptions\ContactIdentityChangedException;
use App\Domain\Party\Exceptions\ContactNotFoundException;
use App\Domain\Party\Exceptions\DuplicateCompanyNumberException;
use App\Domain\Party\Exceptions\IncompleteBusinessAddressException;
use App\Domain\Party\ValueObjects\BankAccountId;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\User\Exceptions\EntityLimitExceededException;
use App\Domain\User\ValueObjects\UserId;

/**
 * Turns the user's selection into the parties an invoice stores. The business and its account are copied when
 * the invoice is created, when the business changes and when the user reloads it; otherwise the stored copy
 * stays, so a later change in the address book never changes a saved invoice. The customer is stored as entered.
 */
final class InvoicePartiesAssembler
{
    public function __construct(
        private readonly BusinessApplicationService $businesses,
        private readonly ContactApplicationService $contacts,
        private readonly BankAccountApplicationService $accounts,
    ) {}

    /**
     * Runs inside the transaction that stores the invoice: saving the customer to the contacts creates the contact.
     *
     * @param  DocumentParties|null  $stored  the parties of the invoice being updated, null for a new invoice
     *
     * @throws BusinessNotFoundException
     * @throws ContactNotFoundException
     * @throws BankAccountNotFoundException
     * @throws ArchivedReferenceSelectedException
     * @throws IncompleteBusinessAddressException
     * @throws ContactIdentityChangedException
     * @throws DuplicateCompanyNumberException
     * @throws EntityLimitExceededException
     */
    public function assemble(
        UserId $userId,
        InvoicePartySelection $selection,
        ?DocumentParties $stored,
    ): DocumentParties {
        $keepBusiness = $stored !== null &&
            ! $selection->reloadBusiness &&
            $stored->businessId->equals($selection->businessId);
        $copyBusiness = ! $keepBusiness;

        $supplier = $keepBusiness
            ? $stored->supplier
            : $this->supplier(
                $userId,
                $selection->businessId,
                $stored,
            );

        return new DocumentParties(
            businessId: $selection->businessId,
            contactId: $this->contactId(
                $userId,
                $selection,
                $stored,
            ),
            bankAccountId: $selection->bankAccountId,
            supplier: $supplier,
            customer: $selection->customer,
            account: $this->account(
                $userId,
                $selection,
                $stored,
                $copyBusiness,
            ),
        );
    }

    private function supplier(
        UserId $userId,
        BusinessId $businessId,
        ?DocumentParties $stored,
    ): DocumentPartyDTO {
        $business = $this->businesses->get($userId, $businessId);
        if ($business->isArchived() && ! $this->isStored($businessId, $stored?->businessId)) {
            throw new ArchivedReferenceSelectedException($businessId);
        }

        $supplier = DocumentPartyDTO::fromProfile($business->profile);
        if (! $supplier->hasCompleteAddress()) {
            throw new IncompleteBusinessAddressException($businessId);
        }

        return $supplier;
    }

    /**
     * Only an account of the selected business; the stored copy stays while the account and business do.
     */
    private function account(
        UserId $userId,
        InvoicePartySelection $selection,
        ?DocumentParties $stored,
        bool $copyBusiness,
    ): ?DocumentAccountDTO {
        $accountId = $selection->bankAccountId;
        if ($accountId === null) {
            return null;
        }

        $isStored = $this->isStored($accountId, $stored?->bankAccountId);
        if ($isStored && ! $copyBusiness && $stored?->account !== null) {
            return $stored->account;
        }

        $account = $this->accounts->get($userId, $accountId);
        if ($account->owner->businessId === null || ! $account->owner->businessId->equals($selection->businessId)) {
            throw new BankAccountNotFoundException($accountId);
        }
        if ($account->isArchived() && ! $isStored) {
            throw new ArchivedReferenceSelectedException($accountId);
        }

        return DocumentAccountDTO::fromBankAccount($account);
    }

    private function contactId(
        UserId $userId,
        InvoicePartySelection $selection,
        ?DocumentParties $stored,
    ): ?ContactId {
        $contactId = $selection->contactId;
        if ($contactId === null) {
            return $selection->saveCustomerToContacts ? $this->saveToContacts($userId, $selection->customer) : null;
        }

        $contact = $this->contacts->get($userId, $contactId);
        if ($contact->isArchived() && ! $this->isStored($contactId, $stored?->contactId)) {
            throw new ArchivedReferenceSelectedException($contactId);
        }
        if (! $selection->customer->hasIdentityOf($contact->profile)) {
            throw new ContactIdentityChangedException($contactId);
        }

        return $contactId;
    }

    private function saveToContacts(UserId $userId, DocumentPartyDTO $customer): ContactId
    {
        return $this->contacts->create($userId, new ContactWriteData($customer->toProfile(), null))->id;
    }

    private function isStored(
        BusinessId|ContactId|BankAccountId $selected,
        BusinessId|ContactId|BankAccountId|null $stored,
    ): bool {
        return $stored !== null && $stored::class === $selected::class && $stored->value === $selected->value;
    }
}
