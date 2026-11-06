<?php

declare(strict_types=1);

namespace App\Domain\Invoice\Contracts;

use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Invoice\DTO\InvoiceWriteData;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Party\DTO\DocumentParties;
use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\User\ValueObjects\UserId;

interface InvoiceDtoWriteRepositoryInterface
{
    public function create(
        UserId $owner,
        InvoiceWriteData $data,
        DocumentParties $parties,
        Money $total,
    ): InvoiceDTO;

    /** Replaces everything the user entered, the parties and the total; the owner stays. */
    public function update(
        InvoiceId $id,
        InvoiceWriteData $data,
        DocumentParties $parties,
        Money $total,
    ): InvoiceDTO;

    public function setTemplate(InvoiceId $id, string $template): bool;

    /** Sets the contact the customer was copied from; the stored customer data stay. */
    public function linkContact(InvoiceId $id, ContactId $contactId): void;
}
