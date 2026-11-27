<?php

declare(strict_types=1);

namespace App\Application\Invoice\Commands;

use App\Domain\Invoice\Contracts\InvoiceDtoWriteRepositoryInterface;
use App\Domain\Invoice\Services\InvoiceService;
use App\Domain\Party\Contracts\ContactReadRepositoryInterface;
use App\Domain\Party\Exceptions\ArchivedReferenceSelectedException;
use App\Domain\Party\Exceptions\ContactIdentityChangedException;
use App\Domain\Party\Exceptions\ContactNotFoundException;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;

/**
 * Sets only the invoice's contact; the customer data on the invoice stay as they are.
 */
final class LinkInvoiceContactHandler
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
        private readonly InvoiceDtoWriteRepositoryInterface $invoiceWriter,
        private readonly ContactReadRepositoryInterface $contacts,
    ) {}

    /**
     * @throws ContactIdentityChangedException when the contact is registered under another number
     */
    public function handle(LinkInvoiceContact $command, WriteScope $scope): void
    {
        // Only a draft's customer may change its contact (D-050, point 3)
        $invoice = $this->invoiceService->draftForUpdate($scope, $command->id);
        $contact = $this->contacts->findById($command->contactId, $scope->read)
            ?? throw new ContactNotFoundException($command->contactId);
        if ($contact->isArchived()) {
            throw new ArchivedReferenceSelectedException($command->contactId);
        }
        if (!$invoice->parties->customer->hasIdentityOf($contact->profile)) {
            throw new ContactIdentityChangedException($command->contactId);
        }

        $this->invoiceWriter->linkContact(
            $command->id,
            $scope,
            $command->contactId,
        );
    }
}
