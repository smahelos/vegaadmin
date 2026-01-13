<?php

namespace App\Application\Invoice\Contracts;

use App\Domain\Invoice\DTO\PrintableInvoiceData;

interface InvoicePdfRendererInterface
{
    /**
     * Render DomPDF from printable invoice data, with chosen template and locale.
     * @return \Barryvdh\DomPDF\PDF
     */
    public function render(PrintableInvoiceData $data, ?string $template = null, ?string $locale = null);
}
