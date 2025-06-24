<?php

namespace App\Observers;

use App\Models\ProductCategory;
use App\Events\FormDataChanged;

class CategoryObserver
{
    /**
     * Handle the ProductCategory "created" event.
     */
    public function created(ProductCategory $category): void
    {
        // Fire form data cache invalidation event
        FormDataChanged::dispatch('categories');
    }
    
    /**
     * Handle the ProductCategory "updated" event.
     */
    public function updated(ProductCategory $category): void
    {
        // Fire form data cache invalidation event if relevant fields changed
        if ($category->isDirty(['name'])) {
            FormDataChanged::dispatch('categories');
        }
    }
    
    /**
     * Handle the ProductCategory "deleted" event.
     */
    public function deleted(ProductCategory $category): void
    {
        // Fire form data cache invalidation event
        FormDataChanged::dispatch('categories');
    }
}
