<?php

declare(strict_types=1);

namespace App\Domain\Shared\Unit\Enums;

/**
 * Unit of measure of a product or an invoice item. The value is stored in the database
 * and is the translation key under invoices.units.
 */
enum Unit: string
{
    case PIECES = 'pieces';
    case HOURS = 'hours';
    case DAYS = 'days';
    case KILOGRAMS = 'kilograms';
    case GRAMS = 'grams';
    case LITERS = 'liters';
    case MILLILITERS = 'milliliters';
    case METERS = 'meters';
    case CENTIMETERS = 'centimeters';
    case CUBIC_METERS = 'cubic_meters';
    case CUBIC_CENTIMETERS = 'cubic_centimeters';

    /**
     * An item entered without a unit counts in pieces.
     */
    public static function fromInput(?string $value): self
    {
        return $value === null || $value === '' ? self::PIECES : self::from($value);
    }
}
