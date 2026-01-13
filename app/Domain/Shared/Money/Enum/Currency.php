<?php

namespace App\Domain\Shared\Money\Enum;

/**
 * Canonical list of supported ISO 4217 currency codes for the application.
 * Extend carefully – adding new codes is backward compatible, removing is breaking.
 */
enum Currency: string
{
    case CZK = 'CZK';
    case EUR = 'EUR';
    case USD = 'USD';
    case GBP = 'GBP';

    /**
     * Return array of all supported currency codes.
     *
     * @return array<int,string>
     */
    public static function codes(): array
    {
        return array_map(static fn(self $c) => $c->value, self::cases());
    }

    /**
     * Create Currency enum from string, defaulting to CZK if unknown.
     */
    public static function fromString(string $code): self
    {
        return self::tryFrom(strtoupper($code)) ?? self::CZK;
    }
}
