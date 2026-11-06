<?php

declare(strict_types=1);

namespace App\Domain\User\Enums;

/**
 * An entity whose creation the owner's plan role limits (D-047); the value is its column in role_limits.
 */
enum LimitedEntity: string
{
    case INVOICE = 'invoices';
    case EXPENSE = 'expenses';
    case BUSINESS = 'businesses';
    case CONTACT = 'contacts';
    case PRODUCT = 'products';

    /**
     * Invoices and expenses are limited per quota window, the others by the number of active records.
     */
    public function isMonthlyQuota(): bool
    {
        return $this === self::INVOICE || $this === self::EXPENSE;
    }
}
