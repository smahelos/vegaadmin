<?php

namespace App\Domain\Product\ValueObjects;

use InvalidArgumentException;

class ProductName
{
    private string $value;

    /**
     * Constructor
     *
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
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Convert to string
     */
    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * Create from string
     */
    public static function fromString(string $value): self
    {
        return new self($value);
    }
}
