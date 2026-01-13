<?php

namespace App\Application\Invoice\DTO;

/**
 * Data Transfer Object for invoice show view data.
 */
class InvoiceShowData
{
    /** @var array<string,mixed> */
    private array $data;

    /**
     * @param array<string,mixed> $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Attach additional data (fluent) – used to enrich without rebuilding object.
     *
     * @param array<string,mixed> $extra
     */
    public function with(array $extra): self
    {
        $clone = clone $this;
        $clone->data = array_merge($this->data, $extra);
        return $clone;
    }

    /**
     * Return raw data array for passing to view.
     * Expands any DTOs implementing toArray().
     *
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        $expanded = [];
        foreach ($this->data as $key => $value) {
            // Expand only DTO/value objects – keep Eloquent models intact so accessors work in Blade.
            if (is_object($value) && method_exists($value, 'toArray')) {
                // Do NOT expand Eloquent models (they implement toArray but we need dynamic accessors & relations)
                if ($value instanceof \Illuminate\Database\Eloquent\Model) {
                    $expanded[$key] = $value; // preserve model instance
                } else {
                    $expanded[$key] = $value->toArray();
                }
            } else {
                $expanded[$key] = $value;
            }
        }
        return $expanded;
    }
}
