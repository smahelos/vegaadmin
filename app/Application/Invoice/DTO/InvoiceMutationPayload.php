<?php

namespace App\Application\Invoice\DTO;

/**
 * Data carrier for the guest invoice prepared from the HTTP request: validated scalar data and the optional
 * stored logo path.
 */
class InvoiceMutationPayload
{
    /**
     * @param  array<string,mixed>  $validated
     */
    public function __construct(
        public array $validated,
    ) {}

    /** Immutable clone with updated logo */
    public function withLogo(?string $logoPath): self
    {
        $clone = clone $this;
        if ($logoPath) {
            $clone->validated['invoice_logo'] = $logoPath;
        }

        return $clone;
    }
}
