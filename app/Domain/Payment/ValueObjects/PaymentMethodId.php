<?php

namespace App\Domain\Payment\ValueObjects;

class PaymentMethodId
{
    public function __construct(private readonly int $value)
    {
        if ($value <= 0) {
            throw new \InvalidArgumentException('PaymentMethod ID must be positive integer');
        }
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function equals(PaymentMethodId $other): bool
    {
        return $this->value === $other->value;
    }

    public static function fromInt(int $value): self
    {
        return new self($value);
    }
}
