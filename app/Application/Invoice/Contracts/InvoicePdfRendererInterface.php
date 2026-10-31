<?php

namespace App\Application\Invoice\Contracts;

use App\Domain\Invoice\DTO\PrintableInvoiceDTO;
use Barryvdh\DomPDF\PDF;

interface InvoicePdfRendererInterface
{
    /**
     * Render DomPDF from printable invoice data, with chosen template and locale.
     */
    public function render(
        PrintableInvoiceDTO $data,
        ?string $template = null,
        ?string $locale = null,
    ): PDF;
}
