<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\InvoiceProductSyncService;

/**
 * Synchronize products from invoice_text JSON to pivot table
 */
class SyncInvoiceProducts extends Command
{
    protected $signature = 'invoices:sync-products {--invoice-id= : Sync specific invoice ID}';
    protected $description = 'Synchronize products from invoice_text JSON to pivot table';

    public function handle(InvoiceProductSyncService $syncService): int
    {
        $invoiceId = $this->option('invoice-id');
        
        if ($invoiceId) {
            $invoice = \App\Models\Invoice::find($invoiceId);
            
            if (!$invoice) {
                $this->error("Invoice with ID {$invoiceId} not found.");
                return 1;
            }
            
            $this->info("Syncing products for invoice #{$invoiceId}...");
            if ($invoice instanceof \App\Models\Invoice) {
                $syncService->syncProductsFromJson($invoice);
            } else {
                $this->error("Invalid invoice data provided.");
                return 1;
            }
            $this->info('Done!');
        } else {
            $this->info('Syncing products for all invoices...');
            $syncService->syncAllInvoices();
            $this->info('Done!');
        }
        
        return 0;
    }
}
