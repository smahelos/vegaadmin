<?php

declare(strict_types=1);

namespace App\Domain\Invoice\Services;

use App\Domain\Invoice\Contracts\InvoiceDtoReadRepositoryInterface;
use App\Domain\Invoice\Contracts\InvoiceDtoWriteRepositoryInterface;
use App\Domain\Invoice\Contracts\InvoiceServiceInterface;
use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Invoice\DTO\InvoiceProductWriteData;
use App\Domain\Invoice\DTO\InvoiceWriteData;
use App\Domain\Invoice\Events\InvoiceCreated;
use App\Domain\Invoice\Exceptions\InvoiceNotFoundException;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Invoice\ValueObjects\InvoiceNumber;
use App\Domain\Invoice\ValueObjects\InvoiceTotals;
use App\Domain\Party\DTO\DocumentParties;
use App\Domain\Product\Contracts\InvoiceProductDtoWriteRepositoryInterface;
use App\Domain\Shared\Events\Contracts\EventPublisherInterface;
use App\Domain\Shared\Money\Enums\Currency;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\User\ValueObjects\UserId;

final class InvoiceService implements InvoiceServiceInterface
{
    public function __construct(
        private readonly EventPublisherInterface $events,
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
        array $data,
        DocumentParties $parties,
        array $invoiceProducts,
    ): InvoiceDTO {
        $data['user_id'] = $userId->toInt();
        $data['total_amount'] = $this->totalOf($data, $invoiceProducts);
        $invoice = $this->invoiceWriteRepository->create(InvoiceWriteData::fromArray($data, $parties));

        $this->saveInvoiceProducts($invoice->id, $invoiceProducts);

        // Publish Domain Event for new invoice creation via unified publisher
        $this->events->publish(new InvoiceCreated($invoice, $userId->toInt()));

        return $invoice;
    }

    /**
     * Update invoice with its parties and product replacement.
     *
     * @param  list<InvoiceProductWriteData>  $invoiceProducts
     */
    public function updateInvoice(
        UserId $userId,
        InvoiceId $invoiceId,
        array $data,
        DocumentParties $parties,
        array $invoiceProducts,
    ): InvoiceDTO {
        $invoice = $this->invoiceReadRepository->findByIdForUser($invoiceId, $userId);
        if (! $invoice) {
            throw new InvoiceNotFoundException('Invoice not accessible for user');
        }

        $data['user_id'] = $userId->toInt();
        $data['total_amount'] = $this->totalOf($data, $invoiceProducts);
        $invoice = $this->invoiceWriteRepository->update($invoice->id, InvoiceWriteData::fromArray($data, $parties));

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
        $lastInvoiceNumber = $this->invoiceReadRepository->findLastInvoiceNumber($userId, (int) date('Y'));

        return InvoiceNumber::generateNext($lastInvoiceNumber)->getNumber();
    }

    /**
     * The invoice amount is always the sum of its lines; an amount typed into the form is ignored.
     *
     * @param  array<string, mixed>  $data
     * @param  list<InvoiceProductWriteData>  $products
     */
    private function totalOf(array $data, array $products): Money
    {
        return InvoiceTotals::fromProducts(Currency::from((string) $data['payment_currency']), $products)->total();
    }
}
