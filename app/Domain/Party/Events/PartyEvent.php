<?php

declare(strict_types=1);

namespace App\Domain\Party\Events;

use App\Domain\Shared\Events\Contracts\DomainEvent;
use App\Domain\User\ValueObjects\UserId;

/**
 * A change of the user's businesses or contacts. The payload carries ids only: names, emails and notes stay out
 * of logs.
 */
abstract readonly class PartyEvent implements DomainEvent
{
    private string $eventId;

    public function __construct(
        public UserId $userId,
    ) {
        $this->eventId = bin2hex(random_bytes(16));
    }

    public function getEventName(): string
    {
        return static::class;
    }

    public function getEventId(): string
    {
        return $this->eventId;
    }

    /**
     * @return array<string, int>
     */
    public function getPayload(): array
    {
        return ['user_id' => $this->userId->toInt()] + $this->entityPayload();
    }

    /**
     * @return array<string, int>
     */
    abstract protected function entityPayload(): array;
}
