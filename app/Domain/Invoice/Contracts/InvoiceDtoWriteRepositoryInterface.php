<?php

declare(strict_types=1);

namespace App\Domain\Invoice\Contracts;

use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Invoice\DTO\InvoiceWriteData;
use App\Domain\Invoice\Enums\InvoiceStatus;
use App\Domain\Invoice\Enums\InvoiceTemplate;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Party\DTO\DocumentParties;
use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;
use App\Domain\Shared\Money\ValueObjects\Money;

interface InvoiceDtoWriteRepositoryInterface
{
    public function create(
        WriteScope $scope,
        InvoiceWriteData $data,
        DocumentParties $parties,
        Money $total,
    ): InvoiceDTO;

    /** Replaces everything the user entered, the parties and the total; the owner stays. */
    public function update(
        InvoiceId $id,
        WriteScope $scope,
        InvoiceWriteData $data,
        DocumentParties $parties,
        Money $total,
    ): InvoiceDTO;

    public function setTemplate(
        InvoiceId $id,
        WriteScope $scope,
        InvoiceTemplate $template,
    ): void;

    public function setStatus(
        InvoiceId $id,
        WriteScope $scope,
        InvoiceStatus $status,
    ): void;

    /** Sets the contact the customer was copied from; the stored customer data stay. */
    public function linkContact(
        InvoiceId $id,
        WriteScope $scope,
        ContactId $contactId,
    ): void;
}
