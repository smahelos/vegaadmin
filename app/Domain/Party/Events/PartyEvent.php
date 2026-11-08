<?php

declare(strict_types=1);

namespace App\Domain\Party\Events;

use App\Domain\Shared\Events\Contracts\DomainEvent;
use App\Domain\User\ValueObjects\UserId;

/**
 * A change of the user's businesses or contacts.
 */
abstract readonly class PartyEvent implements DomainEvent
{
    public function __construct(
        public UserId $userId,
    ) {}

    public function getEventName(): string
    {
        return static::class;
    }
}
