<?php

namespace App\Application\Payment\Contracts;

use App\Models\Subscription;

/**
 * Read-only operations for Subscription aggregate.
 */
interface SubscriptionReadRepositoryInterface
{
    public function findById(int $id): ?Subscription;
}
