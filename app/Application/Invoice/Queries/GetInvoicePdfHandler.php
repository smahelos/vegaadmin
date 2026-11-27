<?php

declare(strict_types=1);

namespace App\Application\Invoice\Queries;

use App\Application\Invoice\Contracts\InvoicePdfRendererInterface;
use App\Application\Invoice\DTO\PdfInvoiceDTO;
use App\Domain\Invoice\Contracts\InvoiceDtoReadRepositoryInterface;
use App\Domain\Invoice\Contracts\InvoicePrintDataBuilderInterface;
use App\Domain\Invoice\Exceptions\InvoiceNotFoundException;
use App\Domain\Party\Contracts\BusinessReadRepositoryInterface;
use App\Domain\Party\Exceptions\BusinessNotFoundException;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;

final class GetInvoicePdfHandler
{
    public function __construct(
        private readonly InvoiceDtoReadRepositoryInterface $invoices,
        private readonly InvoicePrintDataBuilderInterface $printData,
        private readonly BusinessReadRepositoryInterface $businesses,
        private readonly InvoicePdfRendererInterface $renderer,
    ) {}

    public function handle(GetInvoicePdf $query, ReadScope $scope): PdfInvoiceDTO
    {
        $invoice = $this->invoices->findById($query->id, $scope)
            ?? throw new InvoiceNotFoundException('Invoice not accessible for user');
        $businessId = $invoice->parties->businessId;
        // A draft prints the business's current settings, an issued invoice the ones copied at issue (D-050, point 6)
        $business = $this->businesses->findById($businessId, $scope)
            ?? throw new BusinessNotFoundException($businessId);
        $printable = $this->printData->build(
            $invoice,
            $business->invoicing,
        );
        // The document is in its own language, whoever downloads it (D-050, point 3)
        $locale = $invoice->language->value;
        $pdf = $this->renderer->render($printable, $locale);

        return new PdfInvoiceDTO(
            'faktura-' . ($printable->number ?? (string) $query->id->value) . '.pdf',
            $pdf->output(),
            $locale,
        );
    }
}
