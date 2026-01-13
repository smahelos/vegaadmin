<?php

namespace App\Domain\Party\Events;

use App\Domain\Shared\Events\Contracts\DomainEvent;
use App\Domain\Party\DTO\SupplierDTO;

/**
 * Domain event fired when a new supplier is created.
 * 
 * Triggered by:
 * - InvoicePartyService::resolveOrCreateSupplier() when creating new supplier
 * - InvoicePartyService::resolveOrCreateSupplierWithFlag() when creating new supplier
 * 
 * Used for:
 * - Usage limit recording
 * - Audit logging
 * - Supplier onboarding
 * - Analytics tracking
 */
class SupplierCreated implements DomainEvent
{
    /**
     * The type of change that occurred
     *
     * @var string
     */
    public readonly string $changeType;

    /** Occurrence timestamp for potential auditing. */
    public readonly \DateTimeImmutable $occurredOn;

    /** The ID of the user whose data changed. */
    public readonly int $userId;

    /** Event ID for tracking purposes */
    private readonly string $eventId;
    
    public function __construct(
        private readonly SupplierDTO $supplier,
        ?int $userId = null,
        private readonly array $metadata = [],
        string $changeType = 'general'
    ) {
        $this->userId = (int) $userId;
        $this->changeType = $changeType;
        $this->occurredOn = new \DateTimeImmutable('now');
        $this->eventId = uniqid('user_', true);
    }

    public function getSupplier(): SupplierDTO
    {
        return $this->supplier;
    }

    public function getAggregateId(): int
    {
        return $this->supplier->id;
    }

    public function getDomain(): string
    {
        return 'party';
    }

    public function getPayload(): array
    {
        return [
            'user_id' => $this->userId,
            'change_type' => $this->changeType,
            'occurred_on' => $this->occurredOn->format('Y-m-d H:i:s'),
            'supplier_id' => $this->supplier->id,
            'supplier_name' => $this->supplier->name,
            'supplier_email' => $this->supplier->email,
            'supplier_phone' => $this->supplier->phone,
            'supplier_country' => $this->supplier->country,
            'is_default' => $this->supplier->is_default,
            'has_payment_info' => !empty($this->supplier->iban) || !empty($this->supplier->account_number),
        ];
    }

    /** @inheritDoc */
    public function getEventName(): string
    {
        return static::class;
    }

    /** @inheritDoc */
    public function getEventId(): string
    {
        return $this->eventId;
    }

    /** @inheritDoc */
    public function getOccurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    /**
     * Get event metadata for debugging and serialization
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
