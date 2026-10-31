<?php

namespace App\Domain\Invoice\Contracts;

use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Invoice\DTO\InvoiceWriteData;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Party\ValueObjects\ContactId;

/**
 * Contract for Invoice DTO write operations.
 */
interface InvoiceDtoWriteRepositoryInterface
{
    public function create(InvoiceWriteData $writeData): InvoiceDTO;

    public function update(InvoiceId $id, InvoiceWriteData $writeData): InvoiceDTO;

    public function setTemplate(InvoiceId $id, string $template): bool;

    /** Sets the contact the customer was copied from; the stored customer data stay. */
    public function linkContact(InvoiceId $id, ContactId $contactId): void;
}
