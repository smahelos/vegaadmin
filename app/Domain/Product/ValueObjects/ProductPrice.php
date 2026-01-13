<?php

namespace App\Domain\Product\ValueObjects;

use InvalidArgumentException;

class ProductPrice
{
    private float $value;

    /**
     * Constructor
     *
     * @param float $value
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
     *
     * @return float
     */
    public function getValue(): float
    {
        return $this->value;
    }

    /**
     * Get formatted price
     *
     * @return string
     */
    public function getFormatted(): string
    {
        return number_format($this->value, 2, '.', '');
    }

    /**
     * Convert to string
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->getFormatted();
    }

    /**
     * Create from float
     *
     * @param float $value
     * @return self
     */
    public static function fromFloat(float $value): self
    {
        return new self($value);
    }

    /**
     * Create from string
     *
     * @param string $value
     * @return self
     */
    public static function fromString(string $value): self
    {
        $numericValue = (float) $value;
        return new self($numericValue);
    }

    /**
     * Check if equals to another ProductPrice
     *
     * @param ProductPrice $other
     * @return bool
     */
    public function equals(ProductPrice $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * Check if price is zero
     *
     * @return bool
     */
    public function isZero(): bool
    {
        return $this->value === 0.0;
    }

    /**
     * Add another price
     *
     * @param ProductPrice $other
     * @return self
     */
    public function add(ProductPrice $other): self
    {
        return new self($this->value + $other->value);
    }

    /**
     * Subtract another price
     *
     * @param ProductPrice $other
     * @return self
     */
    public function subtract(ProductPrice $other): self
    {
        return new self($this->value - $other->value);
    }

    /**
     * Multiply by quantity
     *
     * @param float $multiplier
     * @return self
     */
    public function multiply(float $multiplier): self
    {
        return new self($this->value * $multiplier);
    }
}
