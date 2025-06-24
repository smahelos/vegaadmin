<?php

namespace App\Observers;

use App\Models\Supplier;
use App\Events\UserDataChanged;

class SupplierObserver
{
    /**
     * Handle the Supplier "created" event.
     */
    public function created(Supplier $supplier): void
    {
        // Fire cache invalidation event for supplier-related dashboard stats
        if ($supplier->user) {
            UserDataChanged::dispatch($supplier->user, 'supplier');
        }
    }
    
    /**
     * Handle the Supplier "updated" event.
     */
    public function updated(Supplier $supplier): void
    {
        // Fire cache invalidation event if relevant fields changed
        if ($supplier->isDirty(['name', 'email'])) {
            if ($supplier->user) {
                UserDataChanged::dispatch($supplier->user, 'supplier');
            }
        }
    }
    
    /**
     * Handle the Supplier "deleted" event.
     */
    public function deleted(Supplier $supplier): void
    {
        // Fire cache invalidation event for supplier-related stats
        if ($supplier->user) {
            UserDataChanged::dispatch($supplier->user, 'supplier');
        }
    }
}
