<?php

declare(strict_types=1);

namespace App\Domain\Party\Enums;

/**
 * Why a registration number was refused; the value is the last segment of its validation message key.
 */
enum RegistrationNumberProblem: string
{
    case MISSING_COUNTRY = 'missing_country';
    case CZECH_FORMAT = 'czech_format';
    case CZECH_CHECKSUM = 'czech_checksum';
}
