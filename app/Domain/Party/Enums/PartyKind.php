<?php

declare(strict_types=1);

namespace App\Domain\Party\Enums;

/**
 * The two entities of the Party context (D-045).
 */
enum PartyKind: string
{
    case BUSINESS = 'business';
    case CONTACT = 'contact';
}
