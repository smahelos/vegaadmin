<?php

namespace App\Contracts;

use App\Models\Invoice;

interface InvoiceProductSyncServiceInterface
{
    /**
     * Synchronize products from invoice_text JSON to pivot table
     *
     * @param Invoice $invoice
     * @return void
     */
    public function syncProductsFromJson(Invoice $invoice): void;

    /**
     * Bulk update of all invoices
     * Use with caution - might be resource intensive for large databases
     *
     * @return void
     */
    public function syncAllInvoices(): void;
}
