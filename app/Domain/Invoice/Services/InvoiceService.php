<?php

declare(strict_types=1);

namespace App\Domain\Invoice\Services;

use App\Domain\Invoice\Contracts\InvoiceDtoReadRepositoryInterface;
use App\Domain\Invoice\Contracts\InvoiceDtoWriteRepositoryInterface;
use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Invoice\DTO\InvoiceIssue;
use App\Domain\Invoice\DTO\InvoiceProductWriteData;
use App\Domain\Invoice\DTO\InvoiceWriteData;
use App\Domain\Invoice\Enums\InvoiceStatus;
use App\Domain\Invoice\Exceptions\InvoiceIssuedException;
use App\Domain\Invoice\Exceptions\InvoiceNotFoundException;
use App\Domain\Invoice\Exceptions\InvoiceNotIssuedException;
use App\Domain\Invoice\Exceptions\InvoiceNumberTakenException;
use App\Domain\Invoice\Exceptions\InvoiceNumberYearException;
use App\Domain\Invoice\Exceptions\NonPayerVatRateException;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Invoice\ValueObjects\InvoiceNumber;
use App\Domain\Invoice\ValueObjects\InvoiceTotals;
use App\Domain\Party\DTO\DocumentParties;
use App\Domain\Party\DTO\InvoicingSettings;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;
use DateTimeImmutable;

/**
 * The life cycle of an invoice (D-050): a draft is written freely, issuing fixes it, and an issued invoice
 * changes only its payment status.
 */
final class InvoiceService
{
    public function __construct(
        private readonly InvoiceDtoWriteRepositoryInterface $invoiceWriteRepository,
        private readonly InvoiceDtoReadRepositoryInterface $invoiceReadRepository,
    ) {}

    /**
     * A new invoice is an unpaid draft. The parties come checked and copied from the application.
     *
     * @param  list<InvoiceProductWriteData>  $invoiceProducts
     * @param  bool  $vatPayer  Of the supplier business; a non-payer's lines have rate 0
     *
     * @throws NonPayerVatRateException
     */
    public function createInvoice(
        WriteScope $scope,
        InvoiceWriteData $data,
        DocumentParties $parties,
        array $invoiceProducts,
        bool $vatPayer,
    ): InvoiceDTO {
        $totals = InvoiceTotals::forSupplier(
            $data->currency,
            $invoiceProducts,
            $vatPayer,
        );

        return $this->invoiceWriteRepository->create(
            $scope,
            $data,
            $parties,
            $totals->total(),
            $invoiceProducts,
            InvoiceStatus::PENDING,
        );
    }

    /**
     * Replaces the draft, its parties and its lines.
     *
     * @param  list<InvoiceProductWriteData>  $invoiceProducts
     * @param  bool  $vatPayer  Of the supplier business; a non-payer's lines have rate 0
     *
     * @throws NonPayerVatRateException
     * @throws InvoiceIssuedException
     */
    public function updateInvoice(
        WriteScope $scope,
        InvoiceId $invoiceId,
        InvoiceWriteData $data,
        DocumentParties $parties,
        array $invoiceProducts,
        bool $vatPayer,
    ): InvoiceDTO {
        $this->draftForUpdate($scope, $invoiceId);
        $totals = InvoiceTotals::forSupplier(
            $data->currency,
            $invoiceProducts,
            $vatPayer,
        );

        return $this->invoiceWriteRepository->update(
            $invoiceId,
            $scope,
            $data,
            $parties,
            $totals->total(),
            $invoiceProducts,
        );
    }

    /**
     * Locks the invoice for the change that follows; only a draft may change.
     *
     * @throws InvoiceNotFoundException
     * @throws InvoiceIssuedException
     */
    public function draftForUpdate(WriteScope $scope, InvoiceId $id): InvoiceDTO
    {
        $invoice = $this->invoiceReadRepository->findByIdForUpdate($id, $scope)
            ?? throw new InvoiceNotFoundException('Invoice not accessible for user');
        if ($invoice->issue !== null) {
            throw new InvoiceIssuedException('An issued invoice cannot be changed');
        }

        return $invoice;
    }

    /**
     * Gives the draft its number and dates, copies the supplier's invoicing settings and stores the VAT lines
     * by rate (D-050, points 6, 7, 9 and 10). An empty tax point date is the issue date.
     *
     * @throws InvoiceNumberYearException when the number is not of the issue date's series
     * @throws InvoiceNumberTakenException when another document of the owner has the number
     * @throws NonPayerVatRateException when the supplier is no longer a VAT payer and a line has a rate
     */
    public function issueInvoice(
        WriteScope $scope,
        InvoiceId $id,
        InvoiceNumber $number,
        DateTimeImmutable $date,
        ?DateTimeImmutable $taxPointDate,
        InvoicingSettings $settings,
        DateTimeImmutable $now,
    ): InvoiceDTO {
        $draft = $this->draftForUpdate($scope, $id);
        $year = (int) $date->format('Y');
        if ($number->year() !== $year) {
            throw new InvoiceNumberYearException(
                $this->nextNumber($scope->read, $year),
            );
        }
        $totals = InvoiceTotals::forSupplier(
            $draft->totalAmount->currency,
            $draft->items,
            $settings->vatPayer,
        );
        $issue = new InvoiceIssue(
            number: $number,
            date: $date,
            taxPointDate: $taxPointDate ?? $date,
            issuedAt: $now,
            issuedBy: $scope->owner,
            vatPayer: $settings->vatPayer,
            registerEntry: $settings->registerEntry,
        );

        try {
            return $this->invoiceWriteRepository->issue(
                $id,
                $scope,
                $issue,
                $settings->vatPayer ? $totals->vatLines : [],
            );
        } catch (InvoiceNumberTakenException) {
            throw new InvoiceNumberTakenException(
                $this->nextNumber($scope->read, $year),
            );
        }
    }

    /**
     * A deleted draft is gone for good (D-050, point 2).
     *
     * @return InvoiceDTO The deleted draft, whose supplier logo copy the caller removes
     *
     * @throws InvoiceIssuedException
     */
    public function deleteDraft(WriteScope $scope, InvoiceId $id): InvoiceDTO
    {
        $draft = $this->draftForUpdate($scope, $id);
        $this->invoiceWriteRepository->delete($id, $scope);

        return $draft;
    }

    /**
     * The payment status is the one change an issued invoice allows (D-050, point 3).
     *
     * @throws InvoiceNotFoundException
     * @throws InvoiceNotIssuedException
     */
    public function setStatus(
        WriteScope $scope,
        InvoiceId $id,
        InvoiceStatus $status,
    ): void {
        $invoice = $this->invoiceReadRepository->findByIdForUpdate($id, $scope)
            ?? throw new InvoiceNotFoundException('Invoice not accessible for user');
        if ($invoice->issue === null) {
            throw new InvoiceNotIssuedException('A draft has no payment status to change');
        }
        $this->invoiceWriteRepository->setStatus(
            $id,
            $scope,
            $status,
        );
    }

    /** The highest number of the owner's series of the year plus one (D-050, point 10). */
    public function nextNumber(ReadScope $scope, int $year): InvoiceNumber
    {
        $lastInvoiceNumber = $this->invoiceReadRepository->findLastInvoiceNumber($scope, $year);

        return InvoiceNumber::generateNext($year, $lastInvoiceNumber);
    }
}
