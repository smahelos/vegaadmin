<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Services\InvoiceProductSyncService;

class InvoiceObserver
{
    protected $syncService;
    
    public function __construct(InvoiceProductSyncService $syncService)
    {
        $this->syncService = $syncService;
    }
    
    /**
     * Handle the Invoice "created" event.
     */
    public function created(Invoice $invoice): void
    {
        $this->syncService->syncProductsFromJson($invoice);
    }
    
    /**
     * Handle the Invoice "updated" event.
     */
    public function updated(Invoice $invoice): void
    {
        // Synchronizovat pouze pokud se změnil invoice_text
        if ($invoice->isDirty('invoice_text')) {
            $this->syncService->syncProductsFromJson($invoice);
        }
    }
}
