<?php

declare(strict_types=1);

namespace App\Application\Invoice\Queries;

use App\Application\Invoice\Contracts\InvoicePdfRendererInterface;
use App\Application\Invoice\DTO\PdfInvoiceDTO;
use App\Domain\Invoice\Contracts\InvoiceDtoReadRepositoryInterface;
use App\Domain\Invoice\Contracts\InvoicePrintDataBuilderInterface;
use App\Domain\Invoice\Exceptions\InvoiceNotFoundException;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;

final class GetInvoicePdfHandler
{
    public function __construct(
        private readonly InvoiceDtoReadRepositoryInterface $invoices,
        private readonly InvoicePrintDataBuilderInterface $printData,
        private readonly InvoicePdfRendererInterface $renderer,
    ) {}

    public function handle(GetInvoicePdf $query, ReadScope $scope): PdfInvoiceDTO
    {
        $invoice = $this->invoices->findById($query->id, $scope)
            ?? throw new InvoiceNotFoundException('Invoice not accessible for user');
        $printable = $this->printData->build($invoice);
        $pdf = $this->renderer->render(
            $printable,
            $printable->template,
            $query->locale,
        );

        return new PdfInvoiceDTO(
            'faktura-' . ($printable->number ?? (string) $query->id->value) . '.pdf',
            $pdf->output(),
        );
    }
}
