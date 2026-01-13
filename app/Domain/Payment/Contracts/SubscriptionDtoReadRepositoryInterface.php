<?php

namespace App\Domain\Payment\Contracts;

use App\Domain\Payment\DTO\SubscriptionDTO;

/**
 * Read-only operations for Subscription aggregate.
 */
interface SubscriptionDtoReadRepositoryInterface
{
    public function findById(int $id): ?SubscriptionDTO;
}
