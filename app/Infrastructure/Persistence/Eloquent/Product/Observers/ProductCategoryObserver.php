<?php

namespace App\Infrastructure\Persistence\Eloquent\Product\Observers;

use App\Models\ProductCategory;
use App\Domain\Shared\Events\FormDataChanged;
use App\Domain\Shared\Events\Contracts\EventPublisherInterface;

class ProductCategoryObserver
{
    /** Handle the ProductCategory "created" event. */
    public function created(ProductCategory $category): void
    {
        $userId = auth()->id() ?? 0;
        app(EventPublisherInterface::class)->publish(new FormDataChanged($userId, 'categories'));
    }

    /** Handle the ProductCategory "updated" event. */
    public function updated(ProductCategory $category): void
    {
        if ($category->isDirty(['name'])) {
            $userId = auth()->id() ?? 0;
            app(EventPublisherInterface::class)->publish(new FormDataChanged($userId, 'categories'));
        }
    }

    /** Handle the ProductCategory "deleted" event. */
    public function deleted(ProductCategory $category): void
    {
        $userId = auth()->id() ?? 0;
        app(EventPublisherInterface::class)->publish(new FormDataChanged($userId, 'categories'));
    }
}
