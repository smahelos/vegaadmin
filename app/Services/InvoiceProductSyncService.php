<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceProductSyncService
{
    /**
     * Synchronize products from invoice_text JSON to pivot table
     *
     * @param Invoice $invoice
     * @return void
     */
    public function syncProductsFromJson(Invoice $invoice)
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
    public function syncAllInvoices()
    {
        $invoices = Invoice::whereNotNull('invoice_text')->get();
        
        foreach ($invoices as $invoice) {
            $this->syncProductsFromJson($invoice);
        }
    }
}
