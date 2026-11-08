<?php

namespace App\Domain\User\Events;

use App\Domain\Shared\Events\Contracts\DomainEvent;

/**
 * Domain event signaling that user-related data has changed.
 * Framework-agnostic: no Laravel traits.
 */
final class UserDataChanged implements DomainEvent
{
    /**
     * The type of change that occurred
     */
    public readonly string $changeType;

    /** The ID of the user whose data changed. */
    public readonly int $userId;

    /**
     * Create a new event instance
     */
    public function __construct(
        int $userId,
        string $changeType = 'general',
        private readonly array $metadata = [],
    ) {
        $this->userId = (int) $userId;
        $this->changeType = $changeType;
    }

    /** {@inheritDoc} */
    public function getEventName(): string
    {
        return self::class;
    }
}
