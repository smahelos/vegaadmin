<?php

namespace App\Domain\Shared\Tax\ValueObjects;

class TaxId
{
    public function __construct(private readonly int $value)
    {
        if ($value <= 0) {
            throw new \InvalidArgumentException('Tax ID must be positive integer');
        }
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function equals(TaxId $other): bool
    {
        return $this->value === $other->value;
    }

    public static function fromInt(int $value): self
    {
        return new self($value);
    }
}
