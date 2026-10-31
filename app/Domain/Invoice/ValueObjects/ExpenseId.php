<?php

declare(strict_types=1);

namespace App\Domain\Invoice\ValueObjects;

use InvalidArgumentException;

final readonly class ExpenseId
{
    private function __construct(public int $value)
    {
        if ($value <= 0) {
            throw new InvalidArgumentException('Expense ID must be positive');
        }
    }

    public static function fromInt(int $value): self
    {
        return new self($value);
    }
}
