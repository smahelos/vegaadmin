<?php

declare(strict_types=1);

namespace App\Domain\Payment\Exceptions;

use DomainException;

final class SubscriptionPlanNotFoundException extends DomainException
{
    public function __construct(int $planId)
    {
        parent::__construct('Subscription plan not found: ' . $planId);
    }
}
