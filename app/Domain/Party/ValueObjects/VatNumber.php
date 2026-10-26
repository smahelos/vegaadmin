<?php

declare(strict_types=1);

namespace App\Domain\Party\ValueObjects;

/**
 * A VAT number as written: it is neither derived from nor checked against the company number.
 */
final readonly class VatNumber
{
    private function __construct(
        public string $value,
    ) {}

    /**
     * Null when the number is empty.
     */
    public static function fromNullable(?string $value): ?self
    {
        $value = trim($value ?? '');

        return $value === '' ? null : new self($value);
    }
}
