<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Contracts\InvoiceProductSyncServiceInterface;
use App\Events\UserDataChanged;

class InvoiceObserver
{
    protected $syncService;
    
    public function __construct(InvoiceProductSyncServiceInterface $syncService)
    {
        $this->syncService = $syncService;
    }
    
    /**
     * Handle the Invoice "created" event.
     */
    public function created(Invoice $invoice): void
    {
        $this->syncService->syncProductsFromJson($invoice);
        
        // Fire cache invalidation event for dashboard and user stats
        if ($invoice->user) {
            UserDataChanged::dispatch($invoice->user, 'invoice');
        }
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
        
        // Fire cache invalidation event if relevant fields changed
        if ($invoice->isDirty(['payment_amount', 'payment_status_id', 'issue_date', 'due_in'])) {
            if ($invoice->user) {
                UserDataChanged::dispatch($invoice->user, 'invoice');
            }
        }
    }
    
    /**
     * Handle the Invoice "deleted" event.
     */
    public function deleted(Invoice $invoice): void
    {
        // Fire cache invalidation event for dashboard stats
        if ($invoice->user) {
            UserDataChanged::dispatch($invoice->user, 'invoice');
        }
    }
}
