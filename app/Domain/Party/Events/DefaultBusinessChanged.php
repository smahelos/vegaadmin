<?php

declare(strict_types=1);

namespace App\Domain\Party\Events;

use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\User\ValueObjects\UserId;

/**
 * The user's default business changed; null when the last active business was archived.
 */
final readonly class DefaultBusinessChanged extends PartyEvent
{
    public function __construct(
        public ?BusinessId $businessId,
        UserId $userId,
    ) {
        parent::__construct($userId);
    }

    protected function entityPayload(): array
    {
        return $this->businessId === null ? [] : ['business_id' => $this->businessId->value];
    }
}
