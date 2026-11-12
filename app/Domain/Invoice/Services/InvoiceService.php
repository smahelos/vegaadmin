<?php

declare(strict_types=1);

namespace App\Domain\Invoice\Services;

use App\Domain\Invoice\Contracts\InvoiceDtoReadRepositoryInterface;
use App\Domain\Invoice\Contracts\InvoiceDtoWriteRepositoryInterface;
use App\Domain\Invoice\Contracts\InvoiceServiceInterface;
use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Invoice\DTO\InvoiceProductWriteData;
use App\Domain\Invoice\DTO\InvoiceWriteData;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Invoice\ValueObjects\InvoiceNumber;
use App\Domain\Invoice\ValueObjects\InvoiceTotals;
use App\Domain\Party\DTO\DocumentParties;
use App\Domain\Product\Contracts\InvoiceProductDtoWriteRepositoryInterface;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;
use App\Domain\Shared\Money\ValueObjects\Money;

final class InvoiceService implements InvoiceServiceInterface
{
    public function __construct(
        private readonly InvoiceDtoWriteRepositoryInterface $invoiceWriteRepository,
        private readonly InvoiceDtoReadRepositoryInterface $invoiceReadRepository,
        private readonly InvoiceProductDtoWriteRepositoryInterface $invoiceProductRepository,
    ) {}

    /**
     * @param  list<InvoiceProductWriteData>  $products
     */
    public function saveInvoiceProducts(InvoiceId $invoiceId, array $products): void
    {
        $this->invoiceProductRepository->bulkCreate($invoiceId, $products);
    }

    /**
     * The parties come checked and copied from the application.
     *
     * @param  list<InvoiceProductWriteData>  $invoiceProducts
     */
    public function createInvoice(
        WriteScope $scope,
        InvoiceWriteData $data,
        DocumentParties $parties,
        array $invoiceProducts,
    ): InvoiceDTO {
        $invoice = $this->invoiceWriteRepository->create(
            $scope,
            $data,
            $parties,
            $this->totalOf($data, $invoiceProducts),
        );

        $this->saveInvoiceProducts($invoice->id, $invoiceProducts);

        return $invoice;
    }

    /**
     * Replaces the invoice, its parties and its lines.
     *
     * @param  list<InvoiceProductWriteData>  $invoiceProducts
     */
    public function updateInvoice(
        WriteScope $scope,
        InvoiceId $invoiceId,
        InvoiceWriteData $data,
        DocumentParties $parties,
        array $invoiceProducts,
    ): InvoiceDTO {
        $invoice = $this->invoiceWriteRepository->update(
            $invoiceId,
            $scope,
            $data,
            $parties,
            $this->totalOf($data, $invoiceProducts),
        );

        $this->invoiceProductRepository->deleteByInvoiceId($invoiceId);
        $this->saveInvoiceProducts($invoiceId, $invoiceProducts);

        return $invoice;
    }

    /** The owner's next invoice number of the year (see InvoiceNumber). */
    public function generateInvoiceNumber(ReadScope $scope, int $year): string
    {
        $lastInvoiceNumber = $this->invoiceReadRepository->findLastInvoiceNumber($scope, $year);

        $number = InvoiceNumber::generateNext($lastInvoiceNumber);

        return $number->getNumber();
    }

    /**
     * The lines are the source of truth for the total (rules/domain.md, Invoices).
     *
     * @param  list<InvoiceProductWriteData>  $products
     */
    private function totalOf(InvoiceWriteData $data, array $products): Money
    {
        $totals = InvoiceTotals::fromProducts($data->currency, $products);

        return $totals->total();
    }
}
