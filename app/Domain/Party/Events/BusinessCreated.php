<?php

declare(strict_types=1);

namespace App\Domain\Party\Events;

use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\User\ValueObjects\UserId;

final readonly class BusinessCreated extends PartyEvent
{
    public function __construct(
        public BusinessId $businessId,
        UserId $userId,
    ) {
        parent::__construct($userId);
    }

    protected function entityPayload(): array
    {
        return ['business_id' => $this->businessId->value];
    }
}
