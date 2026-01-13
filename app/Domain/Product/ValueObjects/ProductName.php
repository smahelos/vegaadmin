<?php

namespace App\Domain\Product\ValueObjects;

use InvalidArgumentException;

class ProductName
{
    private string $value;

    /**
     * Constructor
     *
     * @param string $value
     * @throws InvalidArgumentException
     */
    public function __construct(string $value)
    {
        $value = trim($value);
        
        if (empty($value)) {
            throw new InvalidArgumentException('Product name cannot be empty');
        }

        if (strlen($value) > 255) {
            throw new InvalidArgumentException('Product name cannot exceed 255 characters');
        }

        $this->value = $value;
    }

    /**
     * Get the value
     *
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Convert to string
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * Create from string
     *
     * @param string $value
     * @return self
     */
    public static function fromString(string $value): self
    {
        return new self($value);
    }

    /**
     * Check if equals to another ProductName
     *
     * @param ProductName $other
     * @return bool
     */
    public function equals(ProductName $other): bool
    {
        return $this->value === $other->value;
    }
}
