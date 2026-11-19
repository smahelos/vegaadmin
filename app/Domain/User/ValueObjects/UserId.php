<?php

declare(strict_types=1);

namespace App\Domain\User\ValueObjects;

use InvalidArgumentException;

final readonly class UserId
{
    public function __construct(
        public int $value,
    ) {
        if ($value <= 0) {
            throw new InvalidArgumentException('User ID must be positive integer');
        }
    }

    public static function fromInt(int $value): self
    {
        return new self($value);
    }
}
