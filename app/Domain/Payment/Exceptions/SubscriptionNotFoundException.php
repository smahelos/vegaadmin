<?php

declare(strict_types=1);

namespace App\Domain\Payment\Exceptions;

use DomainException;

final class SubscriptionNotFoundException extends DomainException
{
    public function __construct(int $subscriptionId)
    {
        parent::__construct('Subscription not found: ' . $subscriptionId);
    }
}
