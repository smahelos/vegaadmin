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
use App\Domain\User\Contracts\Locale;

final class GetInvoicePdfHandler
{
    public function __construct(
        private readonly InvoiceDtoReadRepositoryInterface $invoices,
        private readonly InvoicePrintDataBuilderInterface $printData,
        private readonly BusinessReadRepositoryInterface $businesses,
        private readonly InvoicePdfRendererInterface $renderer,
        private readonly Locale $locale,
    ) {}

    public function handle(GetInvoicePdf $query, ReadScope $scope): PdfInvoiceDTO
    {
        $invoice = $this->invoices->findById($query->id, $scope)
            ?? throw new InvoiceNotFoundException('Invoice not accessible for user');
        $businessId = $invoice->parties->businessId;
        // Until issuing stores the flag on the invoice (D-050, point 6), it is the business's current one
        $business = $this->businesses->findById($businessId, $scope)
            ?? throw new BusinessNotFoundException($businessId);
        $printable = $this->printData->build(
            $invoice,
            $business->invoicing->vatPayer,
        );
        // An unsupported requested language would break the formatters, so it falls back to the current one
        $locale = $this->locale->determineLocale(
            $query->locale,
            $this->locale->getLocale(),
        );
        $pdf = $this->renderer->render($printable, $locale);

        return new PdfInvoiceDTO(
            'faktura-' . ($printable->number ?? (string) $query->id->value) . '.pdf',
            $pdf->output(),
            $locale,
        );
    }
}
