<?php

declare(strict_types=1);

namespace App\Application\Invoice\Commands;

use App\Application\Party\Commands\CreateContact;
use App\Application\Shared\UseCase\Actor;
use App\Application\Shared\UseCase\Contracts\AuthorizingUseCaseRunnerInterface;
use App\Domain\Invoice\Contracts\InvoiceDtoReadRepositoryInterface;
use App\Domain\Invoice\Contracts\InvoiceDtoWriteRepositoryInterface;
use App\Domain\Invoice\Exceptions\InvoiceNotFoundException;
use App\Domain\Party\DTO\ContactWriteData;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;

/**
 * Sets only the invoice's contact; the customer data on the invoice stay as they are. An invoice already linked
 * keeps its contact.
 */
final class SaveInvoiceCustomerToContactsHandler
{
    public function __construct(
        private readonly InvoiceDtoReadRepositoryInterface $invoices,
        private readonly InvoiceDtoWriteRepositoryInterface $invoiceWriter,
        private readonly AuthorizingUseCaseRunnerInterface $useCases,
    ) {}

    public function handle(SaveInvoiceCustomerToContacts $command, WriteScope $scope): void
    {
        $invoice = $this->invoices->findByIdForUpdate($command->id, $scope)
            ?? throw new InvoiceNotFoundException('Invoice not accessible for user');
        if ($invoice->parties->contactId !== null) {
            return;
        }

        $contact = $this->useCases->run(
            Actor::user($command->owner),
            new CreateContact(
                $command->owner,
                new ContactWriteData($invoice->parties->customer->toProfile(), null),
            ),
        );
        $this->invoiceWriter->linkContact(
            $invoice->id,
            $scope,
            $contact->id,
        );
    }
}
