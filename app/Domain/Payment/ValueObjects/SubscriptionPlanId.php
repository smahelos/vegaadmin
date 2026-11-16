<?php

declare(strict_types=1);

namespace App\Domain\Payment\ValueObjects;

use InvalidArgumentException;

final readonly class SubscriptionPlanId
{
    private function __construct(public int $value)
    {
        if ($value <= 0) {
            throw new InvalidArgumentException('Subscription plan ID must be positive');
        }
    }

    public static function fromInt(int $value): self
    {
        return new self($value);
    }
}
