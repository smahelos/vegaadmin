<?php

namespace App\Application\Invoice\Contracts;

use App\Domain\Invoice\DTO\PrintableInvoiceData;
use Barryvdh\DomPDF\PDF;

interface InvoicePdfRendererInterface
{
    /**
     * Render DomPDF from printable invoice data, with chosen template and locale.
     */
    public function render(
        PrintableInvoiceData $data,
        ?string $template = null,
        ?string $locale = null,
    ): PDF;
}
