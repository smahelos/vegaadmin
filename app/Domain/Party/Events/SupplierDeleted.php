<?php

namespace App\Domain\Party\Events;

use App\Domain\Shared\Events\Contracts\DomainEvent;
use App\Domain\Party\DTO\SupplierDTO;

/**
 * Domain event fired when a supplier is deleted.
 * 
 * Triggered by:
 * - InvoicePartyService::deleteSupplier() when removing supplier
 * 
 * Used for:
 * - Usage limit decrementing
 * - Audit logging
 * - Cleanup of related data
 * - Analytics tracking
 */
class SupplierDeleted implements DomainEvent
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
        private readonly string $deleteReason = '',
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

    public function getDeleteReason(): string
    {
        return $this->deleteReason;
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
            'delete_reason' => $this->deleteReason,
            'was_default' => $this->supplier->is_default,
            'had_payment_info' => !empty($this->supplier->iban) || !empty($this->supplier->account_number),
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
