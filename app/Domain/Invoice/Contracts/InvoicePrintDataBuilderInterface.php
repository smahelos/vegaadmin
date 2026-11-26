<?php

namespace App\Domain\Invoice\Contracts;

use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Invoice\DTO\PrintableInvoiceDTO;

/**
 * Domain contract: builds printable invoice data from InvoiceDTO with business rules only.
 */
interface InvoicePrintDataBuilderInterface
{
    /**
     * @param  bool  $vatPayer  Whether the supplier states VAT on the invoice
     */
    public function build(InvoiceDTO $invoice, bool $vatPayer): PrintableInvoiceDTO;
}
