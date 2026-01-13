<?php

namespace App\Domain\Product\ValueObjects;

use InvalidArgumentException;

/**
 * Value object representing a product SKU (Stock Keeping Unit).
 * Rules:
 *  - Alphanumeric plus dashes/underscores only
 *  - Uppercased internally
 *  - Length 3-32 characters
 *  - No consecutive dashes or underscores
 *  - Must start with a letter
 */
class ProductSku
{
    private string $value;

    private const MIN_LENGTH = 3;
    private const MAX_LENGTH = 32;

    public function __construct(string $value)
    {
        $normalized = strtoupper(trim($value));
        $this->validate($normalized);
        $this->value = $normalized;
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function equals(ProductSku $other): bool
    {
        return $this->value === $other->value();
    }

    private function validate(string $value): void
    {
        $length = strlen($value);
        if ($length < self::MIN_LENGTH || $length > self::MAX_LENGTH) {
            throw new InvalidArgumentException("SKU length must be between " . self::MIN_LENGTH . " and " . self::MAX_LENGTH . " characters.");
        }
        if (!preg_match('/^[A-Z][A-Z0-9_-]*$/', $value)) {
            throw new InvalidArgumentException('SKU must start with a letter and contain only A-Z, 0-9, dash or underscore.');
        }
        if (preg_match('/(--|__|-_ |_-)/', $value)) {
            // protect against consecutive separators (allow mixed but single)
            if (preg_match('/(--|__)/', $value)) {
                throw new InvalidArgumentException('SKU cannot contain consecutive separators.');
            }
        }
    }
}
