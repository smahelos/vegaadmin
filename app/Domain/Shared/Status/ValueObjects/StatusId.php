<?php

namespace App\Domain\Shared\Status\ValueObjects;

class StatusId
{
    public function __construct(private readonly int $value)
    {
        if ($value <= 0) {
            throw new \InvalidArgumentException('Status ID must be positive integer');
        }
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function equals(StatusId $other): bool
    {
        return $this->value === $other->value;
    }

    public static function fromInt(int $value): self
    {
        return new self($value);
    }
}
