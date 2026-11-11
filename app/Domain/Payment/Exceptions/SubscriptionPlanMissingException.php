<?php

declare(strict_types=1);

namespace App\Domain\Payment\Exceptions;

use DomainException;

final class SubscriptionPlanMissingException extends DomainException
{
    public function __construct(int $subscriptionId)
    {
        parent::__construct('Subscription has no plan: ' . $subscriptionId);
    }
}
