<?php

declare(strict_types=1);

namespace App\Domain\Payment\Contracts;

use App\Domain\Payment\DTO\SubscriptionDTO;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;

/**
 * Read-only operations for Subscription aggregate.
 */
interface SubscriptionDtoReadRepositoryInterface
{
    public function findById(int $id): ?SubscriptionDTO;

    /** The owner's active subscription with its plan. */
    public function findActive(ReadScope $scope): ?SubscriptionDTO;
}
