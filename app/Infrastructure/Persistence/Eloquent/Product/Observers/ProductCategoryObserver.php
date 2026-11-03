<?php

namespace App\Infrastructure\Persistence\Eloquent\Product\Observers;

use App\Domain\Shared\Events\Contracts\EventPublisherInterface;
use App\Domain\Shared\Events\FormDataChanged;
use App\Models\ProductCategory;

class ProductCategoryObserver
{
    /** Handle the ProductCategory "created" event. */
    public function created(ProductCategory $category): void
    {
        $auth = auth();
        $userId = $auth->id() ?? 0;
        $publisher = app(EventPublisherInterface::class);
        $publisher->publish(
            new FormDataChanged($userId, 'categories'),
        );
    }

    /** Handle the ProductCategory "updated" event. */
    public function updated(ProductCategory $category): void
    {
        if ($category->isDirty(['name'])) {
            $auth = auth();
            $userId = $auth->id() ?? 0;
            $publisher = app(EventPublisherInterface::class);
            $publisher->publish(
                new FormDataChanged($userId, 'categories'),
            );
        }
    }

    /** Handle the ProductCategory "deleted" event. */
    public function deleted(ProductCategory $category): void
    {
        $auth = auth();
        $userId = $auth->id() ?? 0;
        $publisher = app(EventPublisherInterface::class);
        $publisher->publish(
            new FormDataChanged($userId, 'categories'),
        );
    }
}
