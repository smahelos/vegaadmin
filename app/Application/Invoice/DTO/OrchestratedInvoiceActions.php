<?php

namespace App\Application\Invoice\DTO;

/**
 * Immutable command object representing a batch of lightweight frontend actions
 * to perform on an Invoice. Encapsulates normalization (e.g. mark_paid overrides status)
 * and the includeInvoice flag controlling whether a fresh Invoice instance should be
 * attached to a successful result.
 */
class OrchestratedInvoiceActions
{
    /** @var string|null */
    public readonly ?string $template;
    /** @var string|null */
    public readonly ?string $status; // Final resolved status (mark_paid => paid)
    public readonly bool $markPaidRequested; // Raw request flag before normalization
    public readonly bool $includeInvoice; // Whether to attach refreshed Invoice on success

    private function __construct(?string $template, ?string $status, bool $markPaidRequested, bool $includeInvoice)
    {
        $this->template = $template;
        $this->status = $status;
        $this->markPaidRequested = $markPaidRequested;
        $this->includeInvoice = $includeInvoice;
    }

    /**
     * Factory from raw associative array (e.g. validated controller payload).
     * Normalization rules:
     *  - mark_paid=true forces status to 'paid' regardless of provided status
     *  - empty strings are coerced to null
     */
    public static function fromArray(array $actions, bool $includeInvoice = false): self
    {
        $template = isset($actions['template']) && $actions['template'] !== '' ? (string)$actions['template'] : null;
        $markPaid = (bool)($actions['mark_paid'] ?? false);
        $rawStatus = isset($actions['status']) && $actions['status'] !== '' ? (string)$actions['status'] : null;
        $resolvedStatus = $markPaid ? 'paid' : $rawStatus; // precedence
        return new self($template, $resolvedStatus, $markPaid, $includeInvoice);
    }
}
