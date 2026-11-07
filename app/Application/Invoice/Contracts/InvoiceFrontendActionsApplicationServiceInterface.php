<?php

namespace App\Application\Invoice\Contracts;

use App\Application\Invoice\DTO\InvoiceActionResult;
use App\Application\Invoice\DTO\OrchestratedInvoiceActions;

/**
 * Frontend-specific lightweight actions for invoices.
 * Wraps existing InvoiceApplicationService methods to reduce controller LOC via unified result handling.
 */
interface InvoiceFrontendActionsApplicationServiceInterface
{
    /**
     * Mark invoice as paid returning success flag.
     */
    public function markAsPaid(int $userId, int $invoiceId): InvoiceActionResult;

    /**
     * Set invoice template.
     */
    public function setTemplate(
        int $userId,
        int $invoiceId,
        string $template,
    ): InvoiceActionResult;

    /**
     * Orchestrate multiple frontend actions in a single call using command object.
     */
    public function orchestrate(
        int $userId,
        int $invoiceId,
        OrchestratedInvoiceActions $actions,
    ): InvoiceActionResult;
}
