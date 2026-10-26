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

    public function toInt(): int
    {
        return $this->id;
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }
}
