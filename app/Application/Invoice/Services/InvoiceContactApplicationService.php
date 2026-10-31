<?php

declare(strict_types=1);

namespace App\Application\Invoice\Services;

use App\Application\Party\Services\ContactApplicationService;
use App\Domain\Invoice\Contracts\InvoiceDtoReadRepositoryInterface;
use App\Domain\Invoice\Contracts\InvoiceDtoWriteRepositoryInterface;
use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Invoice\Exceptions\InvoiceNotFoundException;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Party\DTO\ContactWriteData;
use App\Domain\Party\DTO\DuplicatePartyDTO;
use App\Domain\Party\Enums\DuplicateReason;
use App\Domain\Party\Exceptions\ArchivedReferenceSelectedException;
use App\Domain\Party\Exceptions\ContactIdentityChangedException;
use App\Domain\Party\Exceptions\ContactNotFoundException;
use App\Domain\Party\Exceptions\DuplicateCompanyNumberException;
use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\Shared\Database\Contracts\TransactionBoundaryInterface;
use App\Domain\User\Exceptions\EntityLimitExceededException;
use App\Domain\User\ValueObjects\UserId;

/**
 * Links the one-off customer of a stored invoice to the address book. Both use cases set only the invoice's
 * contact; the customer data on the invoice stay as they are.
 */
final class InvoiceContactApplicationService
{
    public function __construct(
        private readonly InvoiceDtoReadRepositoryInterface $invoices,
        private readonly InvoiceDtoWriteRepositoryInterface $invoiceWriter,
        private readonly ContactApplicationService $contacts,
        private readonly TransactionBoundaryInterface $transactions,
    ) {}

    /**
     * The contact with the customer's Czech company number, offered to link instead of creating a duplicate.
     *
     * @throws InvoiceNotFoundException
     */
    public function findContactToLink(UserId $userId, InvoiceId $invoiceId): ?DuplicatePartyDTO
    {
        $invoice = $this->get($userId, $invoiceId);
        if ($invoice->parties->contactId !== null) {
            return null;
        }

        $duplicates = $this->contacts->findDuplicates(
            $userId,
            $invoice->parties->customer->toProfile(),
            null,
        );
        foreach ($duplicates as $duplicate) {
            if ($duplicate->reason === DuplicateReason::CZECH_COMPANY_NUMBER) {
                return $duplicate;
            }
        }

        return null;
    }

    /**
     * Creates a contact from the customer, within the user's contact limit.
     *
     * @throws InvoiceNotFoundException
     * @throws DuplicateCompanyNumberException
     * @throws EntityLimitExceededException
     */
    public function saveCustomerToContacts(UserId $userId, InvoiceId $invoiceId): ContactId
    {
        return $this->transactions->transaction(function () use ($userId, $invoiceId): ContactId {
            $invoice = $this->invoices->findByIdForUserForUpdate($invoiceId, $userId)
                ?? throw new InvoiceNotFoundException('Invoice not accessible for user');
            if ($invoice->parties->contactId !== null) {
                return $invoice->parties->contactId;
            }

            $contact = $this->contacts->create(
                $userId,
                new ContactWriteData($invoice->parties->customer->toProfile(), null),
            );
            $this->invoiceWriter->linkContact($invoiceId, $contact->id);

            return $contact->id;
        });
    }

    /**
     * @throws InvoiceNotFoundException
     * @throws ContactNotFoundException
     * @throws ArchivedReferenceSelectedException
     * @throws ContactIdentityChangedException when the contact is registered under another number
     */
    public function linkContact(
        UserId $userId,
        InvoiceId $invoiceId,
        ContactId $contactId,
    ): void {
        $this->transactions->transaction(function () use ($userId, $invoiceId, $contactId): void {
            $invoice = $this->get($userId, $invoiceId);
            $contact = $this->contacts->get($userId, $contactId);
            if ($contact->isArchived()) {
                throw new ArchivedReferenceSelectedException($contactId);
            }
            if (! $invoice->parties->customer->hasIdentityOf($contact->profile)) {
                throw new ContactIdentityChangedException($contactId);
            }

            $this->invoiceWriter->linkContact($invoiceId, $contactId);
        });
    }

    private function get(UserId $userId, InvoiceId $invoiceId): InvoiceDTO
    {
        return $this->invoices->findByIdForUser($invoiceId, $userId)
            ?? throw new InvoiceNotFoundException('Invoice not accessible for user');
    }
}
