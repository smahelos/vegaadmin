<?php

namespace App\Application\Invoice\Contracts;

use App\Domain\Invoice\DTO\PrintableInvoiceDTO;

interface TemporaryInvoicePrintDataFactoryInterface
{
    /**
     * Build PrintableInvoiceDTO from temporary (unsaved) invoice array.
     * Expects keys like 'invoice-products', 'payment_currency', 'invoice_vs', etc.
     */
    public function fromArray(array $invoiceData): PrintableInvoiceDTO;
}
