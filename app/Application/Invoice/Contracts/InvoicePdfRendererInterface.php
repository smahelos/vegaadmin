<?php

declare(strict_types=1);

namespace App\Application\Invoice\Contracts;

use App\Domain\Invoice\DTO\PrintableInvoiceDTO;
use Barryvdh\DomPDF\PDF;

interface InvoicePdfRendererInterface
{
    public function render(PrintableInvoiceDTO $data, string $locale): PDF;
}
