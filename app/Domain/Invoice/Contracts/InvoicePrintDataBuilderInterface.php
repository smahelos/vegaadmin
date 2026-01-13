<?php

namespace App\Domain\Invoice\Contracts;

use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Invoice\DTO\PrintableInvoiceData;

/**
 * Domain contract: builds printable invoice data from InvoiceDTO with business rules only.
 */
interface InvoicePrintDataBuilderInterface
{
    public function build(InvoiceDTO $invoice): PrintableInvoiceData;
}
