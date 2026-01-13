<?php

namespace App\Domain\Party\Events;

use App\Domain\Shared\Events\Contracts\DomainEvent;
use App\Domain\Party\DTO\ClientDTO;

/**
 * Domain event fired when a client is updated.
 * 
 * Triggered by:
 * - InvoicePartyService::updateClient() when modifying client data
 * - InvoicePartyService::setClientDefault() when changing default status
 * 
 * Used for:
 * - Audit logging with change tracking
 * - Data synchronization
 * - Client profile change notifications
 */
class ClientUpdated implements DomainEvent
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
        private readonly ClientDTO $client,
        private readonly array $changedFields = [],
        ?int $userId = null,
        private readonly array $metadata = [],
        string $changeType = 'general'
    ) {
        $this->userId = (int) $userId;
        $this->changeType = $changeType;
        $this->occurredOn = new \DateTimeImmutable('now');
        $this->eventId = uniqid('user_', true);
    }

    public function getClient(): ClientDTO
    {
        return $this->client;
    }

    public function getChangedFields(): array
    {
        return $this->changedFields;
    }

    public function getAggregateId(): int
    {
        return $this->client->id;
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
            'client_id' => $this->client->id,
            'client_name' => $this->client->name,
            'changed_fields' => $this->changedFields,
            'is_default' => $this->client->is_default,
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
