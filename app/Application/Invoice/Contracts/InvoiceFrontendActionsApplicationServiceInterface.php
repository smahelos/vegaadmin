<?php

namespace App\Application\Invoice\Contracts;

use App\Models\User;
use App\Application\Invoice\DTO\InvoiceActionResult;

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
    public function setTemplate(int $userId, int $invoiceId, string $template): InvoiceActionResult;

    /**
     * Change invoice status by slug (e.g. paid, sent, canceled).
     */
    public function changeStatus(int $userId, int $invoiceId, string $statusSlug): InvoiceActionResult;

    /**
     * Get invoices limit data for user (limit, current_usage, allowed).
     * @return array{limit:int,current_usage:int,allowed:bool}
     */
    public function getLimitData(int $userId): InvoiceActionResult;

    /**
     * Orchestrate multiple frontend actions in a single call using command object.
     */
    public function orchestrate(int $userId, int $invoiceId, \App\Application\Invoice\DTO\OrchestratedInvoiceActions $actions): InvoiceActionResult;
}
