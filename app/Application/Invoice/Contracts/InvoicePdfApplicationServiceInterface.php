<?php

namespace App\Application\Invoice\Contracts;

use App\Application\Invoice\DTO\InvoiceActionResult;
use App\Models\User;

interface InvoicePdfApplicationServiceInterface
{
    /**
     * Generate PDF for authenticated user's invoice.
     */
    public function generateForUser(int $userId, int $invoiceId, ?string $locale = null, bool $preview = false): InvoiceActionResult;

    /**
     * Generate PDF for guest invoice via token.
     */
    public function generateForGuestByToken(?string $token, ?string $requestedLocale, bool $preview = false): InvoiceActionResult;

    /**
     * Unified generation entry point. If $user != null and $invoiceId provided -> user flow.
     * Else falls back to guest token flow (token required or resolved from session).
     */
    public function generate(?int $userId, ?int $invoiceId = null, ?string $token = null, ?string $locale = null, bool $preview = false): InvoiceActionResult;
}
