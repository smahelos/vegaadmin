<?php

declare(strict_types=1);

namespace App\Domain\Invoice\Services;

use App\Domain\Invoice\Contracts\InvoiceDtoReadRepositoryInterface;
use App\Domain\Invoice\Contracts\InvoiceDtoWriteRepositoryInterface;
use App\Domain\Invoice\Contracts\InvoiceServiceInterface;
use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Invoice\DTO\InvoiceProductWriteData;
use App\Domain\Invoice\DTO\InvoiceWriteData;
use App\Domain\Invoice\Exceptions\NonPayerVatRateException;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Invoice\ValueObjects\InvoiceNumber;
use App\Domain\Invoice\ValueObjects\InvoiceTotals;
use App\Domain\Party\DTO\DocumentParties;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;
use App\Domain\Shared\Money\ValueObjects\Money;

final class InvoiceService implements InvoiceServiceInterface
{
    public function __construct(
        private readonly InvoiceDtoWriteRepositoryInterface $invoiceWriteRepository,
        private readonly InvoiceDtoReadRepositoryInterface $invoiceReadRepository,
    ) {}

    /**
     * The parties come checked and copied from the application.
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
        return $this->invoiceWriteRepository->create(
            $scope,
            $data,
            $parties,
            $this->totalOf(
                $data,
                $invoiceProducts,
                $vatPayer,
            ),
            $invoiceProducts,
        );
    }

    /**
     * Replaces the invoice, its parties and its lines.
     *
     * @param  list<InvoiceProductWriteData>  $invoiceProducts
     * @param  bool  $vatPayer  Of the supplier business; a non-payer's lines have rate 0
     *
     * @throws NonPayerVatRateException
     */
    public function updateInvoice(
        WriteScope $scope,
        InvoiceId $invoiceId,
        InvoiceWriteData $data,
        DocumentParties $parties,
        array $invoiceProducts,
        bool $vatPayer,
    ): InvoiceDTO {
        return $this->invoiceWriteRepository->update(
            $invoiceId,
            $scope,
            $data,
            $parties,
            $this->totalOf(
                $data,
                $invoiceProducts,
                $vatPayer,
            ),
            $invoiceProducts,
        );
    }

    /** The owner's next invoice number of the year (see InvoiceNumber). */
    public function generateInvoiceNumber(ReadScope $scope, int $year): string
    {
        $lastInvoiceNumber = $this->invoiceReadRepository->findLastInvoiceNumber($scope, $year);

        $number = InvoiceNumber::generateNext($lastInvoiceNumber);

        return $number->value;
    }

    /**
     * The lines are the source of truth for the total (rules/domain.md, Invoices).
     *
     * @param  list<InvoiceProductWriteData>  $products
     *
     * @throws NonPayerVatRateException
     */
    private function totalOf(
        InvoiceWriteData $data,
        array $products,
        bool $vatPayer,
    ): Money {
        $totals = InvoiceTotals::forSupplier(
            $data->currency,
            $products,
            $vatPayer,
        );

        return $totals->total();
    }
}
