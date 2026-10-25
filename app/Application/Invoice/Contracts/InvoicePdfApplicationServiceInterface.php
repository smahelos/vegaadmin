<?php

namespace App\Application\Invoice\Contracts;

use App\Application\Invoice\DTO\InvoiceActionResult;
use App\Models\User;

interface InvoicePdfApplicationServiceInterface
{
    /**
     * Unified generation entry point. If $user != null and $invoiceId provided -> user flow.
     * Else falls back to guest token flow (token required or resolved from session).
     */
    public function generate(
        ?int $userId,
        ?int $invoiceId = null,
        ?string $token = null,
        ?string $locale = null,
        bool $preview = false,
    ): InvoiceActionResult;
}
