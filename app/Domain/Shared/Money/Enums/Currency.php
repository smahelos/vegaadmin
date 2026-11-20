<?php

declare(strict_types=1);

namespace App\Domain\Shared\Money\Enums;

/**
 * ISO 4217 codes an invoice can be issued in, in the order the currency selects offer them: the Czech crown, the
 * European currencies, the US dollar.
 */
enum Currency: string
{
    case CZK = 'CZK';
    case EUR = 'EUR';
    case GBP = 'GBP';
    case PLN = 'PLN';
    case HUF = 'HUF';
    case CHF = 'CHF';
    case USD = 'USD';
}
