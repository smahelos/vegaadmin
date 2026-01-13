<?php

namespace App\Domain\Shared\Status\ValueObjects;

use InvalidArgumentException;

/**
 * Immutable Value Object wrapping StatusCode enum for future extensions (translations, policies).
 */
final class StatusCodeVO
{
    public function __construct(public readonly StatusCode $code) {}

    public static function fromString(string $slug): self
    {
        $enum = StatusCode::tryFromSlug($slug);
        if (!$enum) {
            throw new InvalidArgumentException('Unknown status slug: ' . $slug);
        }
        return new self($enum);
    }

    public function slug(): string
    {
        return $this->code->value;
    }

    public function label(): string
    {
        return $this->code->label();
    }

    public function __toString(): string
    {
        return $this->slug();
    }
}
