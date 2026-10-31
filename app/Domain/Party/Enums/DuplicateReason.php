<?php

declare(strict_types=1);

namespace App\Domain\Party\Enums;

/**
 * What an existing business or contact has in common with the one being saved; the value is the last segment of
 * its message key.
 */
enum DuplicateReason: string
{
    case CZECH_COMPANY_NUMBER = 'czech_company_number';
    case COMPANY_NUMBER = 'company_number';
    case NAME = 'name';
    case EMAIL = 'email';

    /**
     * Only a Czech IČO identifies a party with certainty; the other matches only warn.
     */
    public function isBlocking(): bool
    {
        return $this === self::CZECH_COMPANY_NUMBER;
    }
}
