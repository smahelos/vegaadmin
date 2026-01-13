<?php

namespace App\Domain\Invoice\Contracts;
use App\Domain\Invoice\ValueObjects\InvoiceId;

interface InvoiceProductSyncServiceInterface
{
    /**
     * Synchronize products from invoice_text JSON to persistence by invoice ID.
     */
    public function syncProductsFromJson(InvoiceId $invoiceId): void;

    /**
     * Bulk update of all invoices
     * Use with caution - might be resource intensive for large databases
     *
     * @return void
     */
    public function syncAllInvoices(): void;
}
