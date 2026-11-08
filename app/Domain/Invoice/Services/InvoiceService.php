<?php

declare(strict_types=1);

namespace App\Domain\Invoice\Services;

use App\Domain\Invoice\Contracts\InvoiceDtoReadRepositoryInterface;
use App\Domain\Invoice\Contracts\InvoiceDtoWriteRepositoryInterface;
use App\Domain\Invoice\Contracts\InvoiceServiceInterface;
use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Invoice\DTO\InvoiceProductWriteData;
use App\Domain\Invoice\DTO\InvoiceWriteData;
use App\Domain\Invoice\Exceptions\InvoiceNotFoundException;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Invoice\ValueObjects\InvoiceNumber;
use App\Domain\Invoice\ValueObjects\InvoiceTotals;
use App\Domain\Party\DTO\DocumentParties;
use App\Domain\Product\Contracts\InvoiceProductDtoWriteRepositoryInterface;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\User\ValueObjects\UserId;

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
     * Set invoice pdf template
     */
    public function setInvoiceTemplate(InvoiceId $id, string $template): bool
    {
        // Security (ownership) enforced before calling this domain service.
        return $this->invoiceWriteRepository->setTemplate($id, $template);
    }

    /**
     * Security (ownership) is enforced by passing explicit $userId and scoping persistence there; the parties
     * come checked and copied from the application.
     *
     * @param  list<InvoiceProductWriteData>  $invoiceProducts
     */
    public function createInvoice(
        UserId $userId,
        InvoiceWriteData $data,
        DocumentParties $parties,
        array $invoiceProducts,
    ): InvoiceDTO {
        $invoice = $this->invoiceWriteRepository->create(
            $userId,
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
        UserId $userId,
        InvoiceId $invoiceId,
        InvoiceWriteData $data,
        DocumentParties $parties,
        array $invoiceProducts,
    ): InvoiceDTO {
        $invoice = $this->invoiceReadRepository->findByIdForUser($invoiceId, $userId);
        if (!$invoice) {
            throw new InvoiceNotFoundException('Invoice not accessible for user');
        }

        $invoice = $this->invoiceWriteRepository->update(
            $invoice->id,
            $data,
            $parties,
            $this->totalOf($data, $invoiceProducts),
        );

        $this->invoiceProductRepository->deleteByInvoiceId($invoiceId);
        $this->saveInvoiceProducts($invoiceId, $invoiceProducts);

        return $invoice;
    }

    /**
     * Generate next available invoice number for a given user id.
     *
     * @param  int  $year  Current year for the invoice number
     * @return string Generated invoice number (see InvoiceNumber)
     */
    public function generateInvoiceNumber(UserId $userId, int $year): string
    {
        $lastInvoiceNumber = $this->invoiceReadRepository->findLastInvoiceNumber(
            $userId,
            (int) date('Y'),
        );

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
