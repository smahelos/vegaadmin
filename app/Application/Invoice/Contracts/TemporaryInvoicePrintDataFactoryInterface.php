<?php

namespace App\Application\Invoice\Contracts;

use App\Domain\Invoice\DTO\PrintableInvoiceData;

interface TemporaryInvoicePrintDataFactoryInterface
{
    /**
     * Build PrintableInvoiceData from temporary (unsaved) invoice array.
     * Expects keys like 'invoice-products', 'payment_currency', 'invoice_vs', etc.
     */
    public function fromArray(array $invoiceData): PrintableInvoiceData;
}
