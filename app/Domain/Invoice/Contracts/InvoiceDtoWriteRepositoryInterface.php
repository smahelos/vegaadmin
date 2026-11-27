<?php

declare(strict_types=1);

namespace App\Domain\Invoice\Contracts;

use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Invoice\DTO\InvoiceIssue;
use App\Domain\Invoice\DTO\InvoiceProductWriteData;
use App\Domain\Invoice\DTO\InvoiceWriteData;
use App\Domain\Invoice\Enums\InvoiceStatus;
use App\Domain\Invoice\Enums\InvoiceTemplate;
use App\Domain\Invoice\Exceptions\InvoiceNumberTakenException;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Invoice\ValueObjects\StoredPdf;
use App\Domain\Invoice\ValueObjects\VatLine;
use App\Domain\Party\DTO\DocumentParties;
use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;
use App\Domain\Shared\Money\ValueObjects\Money;

interface InvoiceDtoWriteRepositoryInterface
{
    /** @param  list<InvoiceProductWriteData>  $lines */
    public function create(
        WriteScope $scope,
        InvoiceWriteData $data,
        DocumentParties $parties,
        Money $total,
        array $lines,
        InvoiceStatus $status,
    ): InvoiceDTO;

    /**
     * Replaces everything the user entered, the parties, the total and the lines; the owner stays.
     *
     * @param  list<InvoiceProductWriteData>  $lines
     */
    public function update(
        InvoiceId $id,
        WriteScope $scope,
        InvoiceWriteData $data,
        DocumentParties $parties,
        Money $total,
        array $lines,
    ): InvoiceDTO;

    /**
     * Stores what issuing fixed and the VAT lines by rate.
     *
     * @param  list<VatLine>  $vatLines
     *
     * @throws InvoiceNumberTakenException when another document of the owner has the number
     */
    public function issue(
        InvoiceId $id,
        WriteScope $scope,
        InvoiceIssue $issue,
        array $vatLines,
    ): InvoiceDTO;

    /** Stores the PDF rendered at issue, inside the issuing transaction. */
    public function storePdf(
        InvoiceId $id,
        WriteScope $scope,
        StoredPdf $pdf,
    ): InvoiceDTO;

    /** Deletes the invoice with its lines. */
    public function delete(InvoiceId $id, WriteScope $scope): void;

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
