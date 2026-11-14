<?php

declare(strict_types=1);

namespace App\Domain\Payment\Enums;

enum SubscriptionStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case CANCELLED = 'cancelled';
    case EXPIRED = 'expired';
    case PAST_DUE = 'past_due';
}
