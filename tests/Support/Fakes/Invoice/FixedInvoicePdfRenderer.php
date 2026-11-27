<?php

declare(strict_types=1);

namespace Tests\Support\Fakes\Invoice;

use App\Application\Invoice\Contracts\InvoicePdfRendererInterface;
use App\Domain\Invoice\DTO\PrintableInvoiceDTO;

/** Issues without dompdf where the PDF itself is not under test. */
final class FixedInvoicePdfRenderer implements InvoicePdfRendererInterface
{
    public function render(PrintableInvoiceDTO $data, string $locale): string
    {
        return '%PDF-1.4 fixed';
    }
}
