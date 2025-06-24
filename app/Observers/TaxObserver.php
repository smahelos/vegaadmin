<?php

namespace App\Observers;

use App\Models\Tax;
use App\Events\FormDataChanged;

class TaxObserver
{
    /**
     * Handle the Tax "created" event.
     */
    public function created(Tax $tax): void
    {
        // Fire form data cache invalidation event
        FormDataChanged::dispatch('taxes');
    }
    
    /**
     * Handle the Tax "updated" event.
     */
    public function updated(Tax $tax): void
    {
        // Fire form data cache invalidation event if relevant fields changed
        if ($tax->isDirty(['name', 'rate'])) {
            FormDataChanged::dispatch('taxes');
        }
    }
    
    /**
     * Handle the Tax "deleted" event.
     */
    public function deleted(Tax $tax): void
    {
        // Fire form data cache invalidation event
        FormDataChanged::dispatch('taxes');
    }
}
