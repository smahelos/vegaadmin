<?php

declare(strict_types=1);

namespace App\Domain\Party\ValueObjects;

use InvalidArgumentException;

final readonly class BusinessId
{
    private function __construct(
        public int $value,
    ) {}

    /**
     * @throws InvalidArgumentException when the id is not positive
     */
    public static function fromInt(int $value): self
    {
        if ($value <= 0) {
            throw new InvalidArgumentException("A business id must be positive, got: {$value}");
        }

        return new self($value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
