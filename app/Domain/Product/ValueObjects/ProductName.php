<?php

declare(strict_types=1);

namespace App\Domain\Product\ValueObjects;

use InvalidArgumentException;

final readonly class ProductName
{
    public string $value;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(string $value)
    {
        $value = trim($value);

        if (empty($value)) {
            throw new InvalidArgumentException('Product name cannot be empty');
        }

        if (mb_strlen($value) > 255) {
            throw new InvalidArgumentException('Product name cannot exceed 255 characters');
        }

        $this->value = $value;
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }
}
