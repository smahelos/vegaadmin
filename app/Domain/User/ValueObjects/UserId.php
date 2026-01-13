<?php

namespace App\Domain\User\ValueObjects;

class UserId
{
    public function __construct(private readonly int $value)
    {
        if ($value <= 0) {
            throw new \InvalidArgumentException('User ID must be positive integer');
        }
    }

    public function equals(UserId $other): bool
    {
        return $this->value === $other->value;
    }

    public static function fromInt(int $value): self
    {
        return new self($value);
    }

    public function toInt(): int
    {
        return $this->value;
    }
}
