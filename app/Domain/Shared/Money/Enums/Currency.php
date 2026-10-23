<?php

declare(strict_types=1);

namespace App\Domain\Shared\Money\Enums;

/**
 * ISO 4217 codes an invoice can be issued in.
 */
enum Currency: string
{
    case CZK = 'CZK';
    case EUR = 'EUR';
    case USD = 'USD';
    case GBP = 'GBP';
    case PLN = 'PLN';
    case HUF = 'HUF';
    case CHF = 'CHF';
}
