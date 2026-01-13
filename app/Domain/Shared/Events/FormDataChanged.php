<?php

namespace App\Domain\Shared\Events;

use App\Domain\Shared\Events\Contracts\DomainEvent;

/**
 * Domain event representing that some form-related cached data has changed.
 *
 * This class is framework-agnostic and should not depend on Laravel.
 */
final class FormDataChanged implements DomainEvent
{
    /** Occurrence timestamp for potential auditing. */
    public readonly \DateTimeImmutable $occurredOn;

    /**
     * The type of change that occurred
     *
     * @var string
     */
    public readonly string $changeType;

    /** The ID of the user whose data changed. */
    public readonly int $userId;

    /** Event ID for tracking purposes */
    private readonly string $eventId;

    public function __construct(
        int $userId,
        string $changeType = 'general',
        private readonly array $metadata = [],
    )
    {
        $this->userId = (int) $userId;
        $this->changeType = $changeType;
        $this->occurredOn = new \DateTimeImmutable('now');
        $this->eventId = uniqid('user_', true);
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
            'occurred_on' => $this->occurredOn->format('Y-m-d H:i:s'),
        ];
    }

    /** @inheritDoc */
    public function getOccurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    /** @inheritDoc */
    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
