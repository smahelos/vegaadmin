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
    /**
     * The type of change that occurred
     */
    public readonly string $changeType;

    /** The ID of the user whose data changed. */
    public readonly int $userId;

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
