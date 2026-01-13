<?php

namespace App\Domain\Product\ValueObjects;

use InvalidArgumentException;

/**
 * Product ID Value Object.
 * Ensures type safety and validation for product identifiers.
 */
final class ProductId
{
    public function __construct(
        private readonly int $value
    ) {
        if ($value <= 0) {
            throw new InvalidArgumentException('Product ID must be a positive integer.');
        }
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function equals(ProductId $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }

    public static function fromInt(int $value): self
    {
        return new self($value);
    }
}
