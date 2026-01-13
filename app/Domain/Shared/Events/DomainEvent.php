<?php

namespace App\Domain\Shared\Events;

use Ramsey\Uuid\Uuid;
use App\Domain\Shared\Events\Contracts\DomainEvent as DomainEventContract;

/**
 * Base abstract class for all domain events across all domains.
 *
 * Provides common functionality:
 * - Unique event ID for tracing and deduplication
 * - Timestamp for event ordering and audit
 * - User context for authorization and business logic
 * - Event metadata for serialization and debugging
 */
abstract class DomainEvent implements DomainEventContract
{
    private readonly string $eventId;
    private readonly \DateTimeImmutable $occurredAt;

    public function __construct(
        private readonly ?int $userId = null,
        private readonly array $metadata = []
    ) {
        $this->eventId = Uuid::uuid4()->toString();
        $this->occurredAt = new \DateTimeImmutable();
    }

    /**
     * Get unique event identifier for tracing and deduplication
     */
    public function getEventId(): string
    {
        return $this->eventId;
    }

    /**
     * Get when this event occurred
     */
    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    /**
     * Get user who triggered this event (null for system events)
     */
    public function getUserId(): ?int
    {
        return $this->userId;
    }

    /**
     * Get event metadata for debugging and serialization
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Get event name for dispatcher and handlers registration.
     * By default uses class name, but can be overridden.
     */
    public function getEventName(): string
    {
        return static::class;
    }

    /**
     * Get the aggregate/entity ID this event relates to.
     * Must be implemented by concrete events.
     */
    abstract public function getAggregateId(): int;

    /**
     * Get the domain/context this event belongs to.
     * Must be implemented by concrete events.
     */
    abstract public function getDomain(): string;

    /**
     * Get event payload data for handlers.
     * Must be implemented by concrete events.
     */
    abstract public function getPayload(): array;

    /**
     * Convert event to array for serialization, logging, or API responses
     */
    public function toArray(): array
    {
        return [
            'event_id' => $this->eventId,
            'event_name' => $this->getEventName(),
            'domain' => $this->getDomain(),
            'aggregate_id' => $this->getAggregateId(),
            'user_id' => $this->userId,
            'occurred_at' => $this->occurredAt->format('c'),
            'metadata' => $this->metadata,
            'payload' => $this->getPayload(),
        ];
    }

    /**
     * String representation for debugging
     */
    public function __toString(): string
    {
        return sprintf(
            '%s[%s] (domain: %s, aggregate: %d, user: %s, time: %s)',
            $this->getEventName(),
            $this->eventId,
            $this->getDomain(),
            $this->getAggregateId(),
            $this->userId ?? 'system',
            $this->occurredAt->format('Y-m-d H:i:s')
        );
    }
}
