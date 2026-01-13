<?php

namespace App\Infrastructure\Persistence\Eloquent\User\Observers;

use App\Domain\User\Events\EntityCreatedForLimits;

/**
 * Generic observer dispatching entity usage events for limited entities.
 * Centralizes model => entity type mapping to record usage counts.
 */
class GenericEntityUsageObserver
{
    /**
     * Map model class => entity type slug.
     * Extend this map when adding new limited entities.
     * @var array<class-string,string>
     */
    public static array $entityMap = [
        \App\Models\Client::class => 'client',
        \App\Models\Invoice::class => 'invoice',
        \App\Models\Product::class => 'product',
        \App\Models\Supplier::class => 'supplier',
        \App\Models\Expense::class => 'expense',
    ];

    /**
     * Handle created event generically.
     */
    public function created(object $model): void
    {
        $class = get_class($model);
        $entityType = self::$entityMap[$class] ?? null;
        if (!$entityType) {
            return; // Not a tracked entity
        }
        // Support both direct property and accessor style retrieval
        if (property_exists($model, 'user_id') && $model->user_id) {
            EntityCreatedForLimits::dispatch($model->user_id, $entityType);
            return;
        }
        if (method_exists($model, 'getAttribute')) {
            $userId = $model->getAttribute('user_id');
            if ($userId) {
                EntityCreatedForLimits::dispatch($userId, $entityType);
            }
        }
    }
}
