<?php

declare(strict_types=1);

namespace App\Domain\Payment\ValueObjects;

use InvalidArgumentException;

final readonly class PaymentMethodId
{
    public function __construct(
        public int $value,
    ) {
        if ($value <= 0) {
            throw new InvalidArgumentException('PaymentMethod ID must be positive integer');
        }
    }

    public static function fromInt(int $value): self
    {
        return new self($value);
    }
}
