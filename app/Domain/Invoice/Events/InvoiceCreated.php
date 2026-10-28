<?php

namespace App\Domain\Invoice\Events;

use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Shared\Events\Contracts\DomainEvent;

/**
 * Domain event fired when a new invoice is created.
 *
 * Triggered by:
 * - InvoiceService::createInvoice() when creating new invoice
 *
 * Used for:
 * - Usage limit recording
 * - Audit logging
 * - Invoice analytics tracking
 * - Cache invalidation
 */
class InvoiceCreated implements DomainEvent
{
    /**
     * The type of change that occurred
     */
    public readonly string $changeType;

    /** Occurrence timestamp for potential auditing. */
    public readonly \DateTimeImmutable $occurredOn;

    /** The ID of the user whose data changed. */
    public readonly int $userId;

    /** Event ID for tracking purposes */
    private readonly string $eventId;

    public function __construct(
        private readonly InvoiceDTO $invoice,
        ?int $userId = null,
        private readonly array $metadata = [],
        string $changeType = 'general',
    ) {
        $this->userId = (int) $userId;
        $this->changeType = $changeType;
        $this->occurredOn = new \DateTimeImmutable('now');
        $this->eventId = uniqid('user_', true);
    }

    public function getPayload(): array
    {
        return [
            'change_type' => $this->changeType,
            'occurred_on' => $this->occurredOn->format('Y-m-d H:i:s'),
            'id' => $this->invoice->id,
            'number' => $this->invoice->number,
            'constant_code' => $this->invoice->constant_code,
            'payment_reference' => $this->invoice->payment_reference,
            'payment_method_id' => $this->invoice->payment_method_id,
            'due_in' => $this->invoice->due_in,
            'status' => $this->invoice->status,
            'issue_date' => $this->invoice->issue_date,
            'tax_point_date' => $this->invoice->tax_point_date,
            'total_amount' => $this->invoice->total_amount,
            'payment_currency' => $this->invoice->currency,
            'template' => $this->invoice->template,
            'invoice_logo' => $this->invoice->logo,
            'invoice_text' => $this->invoice->invoice_text,
            'business_id' => $this->invoice->parties->businessId->value,
            'contact_id' => $this->invoice->parties->contactId?->value,
            'user_id' => $this->userId, // Use event context user, not invoice user
            'created_at' => $this->invoice->created_at,
            'updated_at' => $this->invoice->updated_at,
        ];
    }

    /** {@inheritDoc} */
    public function getEventName(): string
    {
        return static::class;
    }

    /** {@inheritDoc} */
    public function getEventId(): string
    {
        return $this->eventId;
    }
}
