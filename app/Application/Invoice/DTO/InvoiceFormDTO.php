<?php

namespace App\Application\Invoice\DTO;

use App\Application\Shared\Form\DTO\FieldSetDTO;

/**
 * Aggregated DTO for invoice form data (create/edit) including field definitions.
 * Phase 2: Allows controller/view to consume a single object instead of manually
 * merging arrays. Provides backward-compatible toArray() for Blade.
 */
class InvoiceFormDTO
{
    /** @param array<string,mixed> $meta */
    public function __construct(
        private readonly FieldSetDTO $fields,
        private readonly array $meta
    ) {}

    public function fields(): FieldSetDTO
    {
        return $this->fields;
    }

    /**
     * Return legacy compatible array for blade merge.
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return array_merge($this->meta, [
            'fields' => $this->fields->toArray(),
        ]);
    }
}
