<?php

namespace App\Domain\Party\ValueObjects;

/**
 * PartyId Value Object encapsulates the identifier (client or supplier) to increase type safety.
 * Immutable. Provides factory methods and basic validation.
 */
final class PartyId
{
    private function __construct(private int $id) {}

    public static function fromInt(int $id): self
    {
        if ($id <= 0) {
            throw new \InvalidArgumentException('PartyId must be positive integer.');
        }
        return new self($id);
    }

    /**
     * Attempt to parse from mixed input (string/int), returning null on invalid.
     */
    public static function tryFrom(mixed $value): ?self
    {
        if (is_int($value)) {
            return $value > 0 ? new self($value) : null;
        }
        if (is_string($value) && ctype_digit($value)) {
            $int = (int)$value;
            return $int > 0 ? new self($int) : null;
        }
        return null;
    }

    public function toInt(): int
    {
        return $this->id;
    }

    public function equals(self $other): bool
    {
        return $this->id === $other->id;
    }

    public function __toString(): string
    {
        return (string)$this->id;
    }
}
