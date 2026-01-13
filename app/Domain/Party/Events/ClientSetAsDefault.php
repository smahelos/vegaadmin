<?php

namespace App\Domain\Party\Events;

use App\Domain\Shared\Events\Contracts\DomainEvent;
use App\Domain\Party\DTO\ClientDTO;

/**
 * Domain event fired when a client is set as default.
 * 
 * Triggered by:
 * - InvoicePartyService::setClientDefault() when making client default
 * 
 * Used for:
 * - UI state updates
 * - User preference tracking
 * - Default client caching
 */
class ClientSetAsDefault implements DomainEvent
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
        private readonly ?ClientDTO $previousDefault = null,
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

    public function getPreviousDefault(): ?ClientDTO
    {
        return $this->previousDefault;
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
            'previous_default_id' => $this->previousDefault?->id,
            'previous_default_name' => $this->previousDefault?->name,
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
