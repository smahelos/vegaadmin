<?php

namespace App\Services;

use App\Contracts\InvoiceProductSyncServiceInterface;
use App\Models\Invoice;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceProductSyncService implements InvoiceProductSyncServiceInterface
{
    /**
     * Synchronize products from invoice_text JSON to pivot table
     *
     * @param Invoice $invoice
     * @return void
     */
    public function syncProductsFromJson(Invoice $invoice): void
    {
        DB::beginTransaction();
        
        try {
            $invoice->syncProductsFromJson();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Failed to sync invoice products: ' . $e->getMessage());
        }
    }
    
    /**
     * Bulk update of all invoices
     * Use with caution - might be resource intensive for large databases
     */
    public function syncAllInvoices(): void
    {
        $invoices = Invoice::whereNotNull('invoice_text')->get();
        
        foreach ($invoices as $invoice) {
            $this->syncProductsFromJson($invoice);
        }
    }
}
