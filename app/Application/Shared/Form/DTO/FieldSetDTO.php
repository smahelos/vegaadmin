<?php

namespace App\Application\Shared\Form\DTO;

/**
 * FieldSetDTO groups multiple FieldDTO instances representing a logical
 * collection (e.g., Invoice form fields). Phase 1 keeps it simple.
 */
class FieldSetDTO
{
    /** @param FieldDTO[] $fields */
    public function __construct(
        private array $fields,
    ) {}

    /**
     * Convert all fields to array form for existing blade expectations.
     *
     * @return array<int,array<string,mixed>>
     */
    public function toArray(): array
    {
        return array_map(static fn (FieldDTO $f) => $f->toArray(), $this->fields);
    }
}
