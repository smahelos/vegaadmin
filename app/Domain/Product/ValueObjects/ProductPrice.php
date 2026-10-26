<?php

namespace App\Domain\Product\ValueObjects;

use InvalidArgumentException;

class ProductPrice
{
    private float $value;

    /**
     * Constructor
     *
     * @throws InvalidArgumentException
     */
    public function __construct(float $value)
    {
        if ($value < 0) {
            throw new InvalidArgumentException('Product price cannot be negative');
        }

        // Round to 2 decimal places for currency precision
        $this->value = round($value, 2);
    }

    /**
     * Get the value
     */
    public function getValue(): float
    {
        return $this->value;
    }

    /**
     * Get formatted price
     */
    public function getFormatted(): string
    {
        return number_format(
            $this->value,
            2,
            '.',
            '',
        );
    }

    /**
     * Convert to string
     */
    public function __toString(): string
    {
        return $this->getFormatted();
    }

    /**
     * Create from float
     */
    public static function fromFloat(float $value): self
    {
        return new self($value);
    }

    /**
     * Check if price is zero
     */
    public function isZero(): bool
    {
        return $this->value === 0.0;
    }

    /**
     * Add another price
     */
    public function add(ProductPrice $other): self
    {
        return new self($this->value + $other->value);
    }
}
