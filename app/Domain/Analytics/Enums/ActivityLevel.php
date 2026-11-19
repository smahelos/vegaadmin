<?php

declare(strict_types=1);

namespace App\Domain\Analytics\Enums;

/**
 * How active a user is, by the invoices created in the last 30 days.
 */
enum ActivityLevel: string
{
    case HIGH = 'high';
    case MEDIUM = 'medium';
    case LOW = 'low';
    case INACTIVE = 'inactive';

    public static function fromInvoices(int $invoicesLast30Days): self
    {
        if ($invoicesLast30Days >= 20) {
            return self::HIGH;
        }
        if ($invoicesLast30Days >= 5) {
            return self::MEDIUM;
        }
        if ($invoicesLast30Days >= 1) {
            return self::LOW;
        }

        return self::INACTIVE;
    }
}
