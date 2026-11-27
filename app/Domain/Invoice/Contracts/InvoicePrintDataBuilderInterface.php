<?php

namespace App\Domain\Invoice\Contracts;

use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Invoice\DTO\PrintableInvoiceDTO;
use App\Domain\Party\DTO\InvoicingSettings;

/**
 * Domain contract: builds printable invoice data from InvoiceDTO with business rules only.
 */
interface InvoicePrintDataBuilderInterface
{
    /**
     * @param  InvoicingSettings  $business  The supplier's current settings, printed on a draft; an issued invoice
     *                                       prints the ones copied at issue
     */
    public function build(InvoiceDTO $invoice, InvoicingSettings $business): PrintableInvoiceDTO;
}
