<?php

declare(strict_types=1);

namespace App\Application\Invoice\Contracts;

use App\Domain\Invoice\DTO\PrintableInvoiceDTO;

interface InvoicePdfRendererInterface
{
    /** @return string The PDF file */
    public function render(PrintableInvoiceDTO $data, string $locale): string;
}
