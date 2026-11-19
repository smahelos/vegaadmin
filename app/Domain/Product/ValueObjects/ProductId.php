<?php

declare(strict_types=1);

namespace App\Domain\Product\ValueObjects;

use InvalidArgumentException;

final readonly class ProductId
{
    public function __construct(
        public int $value,
    ) {
        if ($value <= 0) {
            throw new InvalidArgumentException('Product ID must be a positive integer.');
        }
    }

    public static function fromInt(int $value): self
    {
        return new self($value);
    }
}
