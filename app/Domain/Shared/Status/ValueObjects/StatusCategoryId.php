<?php

namespace App\Domain\Shared\Status\ValueObjects;

class StatusCategoryId
{
    public function __construct(private readonly int $value)
    {
        if ($value <= 0) {
            throw new \InvalidArgumentException('StatusCategory ID must be positive integer');
        }
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function equals(StatusCategoryId $other): bool
    {
        return $this->value === $other->value;
    }

    public static function fromInt(int $value): self
    {
        return new self($value);
    }
}
