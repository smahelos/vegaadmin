<?php

namespace App\Domain\Analytics\Events;

use App\Domain\Shared\Events\Contracts\DomainEvent;

/**
 * Domain event signaling that analytics-related data has changed.
 * Framework-agnostic: no Laravel traits.
 */
final class AnalyticsDataChanged implements DomainEvent
{
    /**
     * The type of change that occurred
     *
     * @var string
     */
    public readonly string $changeType;

    /** Occurrence timestamp for potential auditing. */
    public readonly \DateTimeImmutable $occurredOn;

    /** The ID of the user whose analytics data changed. */
    public readonly int $userId;

    /** Optional entity ID that caused the change (invoice ID, client ID, etc.) */
    public readonly ?int $entityId;

    /** Event ID for tracking purposes */
    private readonly string $eventId;

    /**
     * Create a new event instance
     *
     * @param int $userId User whose analytics data changed
     * @param string $changeType Type of change (invoice, client, supplier, statistics)
     * @param int|null $entityId Optional entity ID that caused the change
     */
    public function __construct(
        int $userId, 
        string $changeType = 'general', 
        ?int $entityId = null,
        private readonly array $metadata = [],
    )
    {
        $this->userId = (int) $userId;
        $this->changeType = $changeType;
        $this->entityId = $entityId;
        $this->occurredOn = new \DateTimeImmutable('now');
        $this->eventId = uniqid('analytics_', true);
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
    public function getPayload(): array
    {
        return [
            'user_id' => $this->userId,
            'change_type' => $this->changeType,
            'entity_id' => $this->entityId,
            'occurred_on' => $this->occurredOn->format('Y-m-d H:i:s'),
        ];
    }

    /** @inheritDoc */
    public function getOccurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    /**
     * Get any additional metadata associated with the event.
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
