<?php

declare(strict_types=1);

namespace App\Domain\Invoice\ValueObjects;

use InvalidArgumentException;

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
}
