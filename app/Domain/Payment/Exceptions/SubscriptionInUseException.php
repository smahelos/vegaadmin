<?php

declare(strict_types=1);

namespace App\Domain\Payment\Exceptions;

use DomainException;

/**
 * A subscription with payments stays: the payments record money that moved.
 */
final class SubscriptionInUseException extends DomainException
{
    public function __construct(int $subscriptionId)
    {
        parent::__construct(
            'Subscription ' . $subscriptionId . ' has payments and cannot be deleted',
        );
    }
}
