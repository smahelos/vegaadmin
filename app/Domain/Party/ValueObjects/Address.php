<?php

declare(strict_types=1);

namespace App\Domain\Party\ValueObjects;

use App\Domain\Party\Exceptions\InvalidAddressException;
use App\Domain\Shared\Geography\Enums\Country;

/**
 * An invoicing address. The street line and postal code are free text, so foreign formats fit.
 */
final readonly class Address
{
    private function __construct(
        public ?string $street,
        public ?string $city,
        public ?string $postalCode,
        public Country $country,
    ) {}

    /**
     * Null when the street, city and postal code are all empty: an empty address is not stored, and a country
     * alone, typically prefilled by the form, does not make one.
     *
     * @throws InvalidAddressException when a part is filled without the country
     */
    public static function fromParts(
        ?string $street,
        ?string $city,
        ?string $postalCode,
        ?Country $country,
    ): ?self {
        $street = self::filled($street);
        $city = self::filled($city);
        $postalCode = self::filled($postalCode);

        if ($street === null && $city === null && $postalCode === null) {
            return null;
        }

        if ($country === null) {
            throw new InvalidAddressException;
        }

        return new self(
            $street,
            $city,
            $postalCode,
            $country,
        );
    }

    private static function filled(?string $value): ?string
    {
        $value = trim($value ?? '');

        return $value === '' ? null : $value;
    }
}
