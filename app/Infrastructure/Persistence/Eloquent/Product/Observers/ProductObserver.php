<?php

namespace App\Infrastructure\Persistence\Eloquent\Product\Observers;

use App\Models\Product;
use App\Domain\Shared\Events\FormDataChanged;
use App\Domain\Shared\Events\Contracts\EventPublisherInterface;

class ProductObserver
{
    /** Handle the Product "created" event. */
    public function created(Product $product): void
    {
        app(EventPublisherInterface::class)->publish(new FormDataChanged((int)$product->user_id, 'products'));
    }

    /** Handle the Product "updated" event. */
    public function updated(Product $product): void
    {
        if ($product->isDirty(['name', 'price', 'category_id'])) {
            app(EventPublisherInterface::class)->publish(new FormDataChanged((int)$product->user_id, 'products'));
        }
    }

    /** Handle the Product "deleted" event. */
    public function deleted(Product $product): void
    {
        app(EventPublisherInterface::class)->publish(new FormDataChanged((int)$product->user_id, 'products'));
    }
}
