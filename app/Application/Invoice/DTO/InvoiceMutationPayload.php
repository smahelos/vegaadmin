<?php

namespace App\Application\Invoice\DTO;

use App\Domain\Invoice\DTO\InvoiceProductWriteData;

/**
 * Data carrier for invoice create/update mutations prepared from HTTP request.
 * Contains validated scalar data, the invoice lines and optional stored logo path.
 */
class InvoiceMutationPayload
{
    /**
     * @param  array<string,mixed>  $validated
     * @param  list<InvoiceProductWriteData>  $products
     */
    public function __construct(
        public array $validated,
        public array $products,
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
