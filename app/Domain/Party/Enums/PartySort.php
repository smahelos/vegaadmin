<?php

declare(strict_types=1);

namespace App\Domain\Party\Enums;

/**
 * What a list of businesses or contacts can be ordered by.
 */
enum PartySort: string
{
    case NAME = 'name';
    case SHORT_NAME = 'short_name';
    case COMPANY_NUMBER = 'company_number';
    case EMAIL = 'email';
    case CITY = 'city';
    case CREATED = 'created';
}
