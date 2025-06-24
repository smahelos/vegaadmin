<?php

namespace App\Observers;

use App\Models\Product;
use App\Events\FormDataChanged;

class ProductObserver
{
    /**
     * Handle the Product "created" event.
     */
    public function created(Product $product): void
    {
        // Fire form data cache invalidation event
        FormDataChanged::dispatch('products');
    }
    
    /**
     * Handle the Product "updated" event.
     */
    public function updated(Product $product): void
    {
        // Fire form data cache invalidation event if relevant fields changed
        if ($product->isDirty(['name', 'price', 'category_id'])) {
            FormDataChanged::dispatch('products');
        }
    }
    
    /**
     * Handle the Product "deleted" event.
     */
    public function deleted(Product $product): void
    {
        // Fire form data cache invalidation event
        FormDataChanged::dispatch('products');
    }
}
