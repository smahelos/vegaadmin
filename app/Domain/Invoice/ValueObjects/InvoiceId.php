<?php

declare(strict_types=1);

namespace App\Domain\Invoice\ValueObjects;

use InvalidArgumentException;

/**
 * Invoice ID Value Object.
 * Ensures type safety and validation for invoice identifiers.
 */
final readonly class InvoiceId
{
    public function __construct(
        public int $value,
    ) {
        if ($value <= 0) {
            throw new InvalidArgumentException('Invoice ID must be a positive integer.');
        }
    }

    public static function fromInt(int $value): self
    {
        return new self($value);
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
