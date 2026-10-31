<?php

declare(strict_types=1);

namespace App\Domain\Party\Events;

use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\User\ValueObjects\UserId;

final readonly class ContactCreated extends PartyEvent
{
    public function __construct(
        public ContactId $contactId,
        UserId $userId,
    ) {
        parent::__construct($userId);
    }

    protected function entityPayload(): array
    {
        return ['contact_id' => $this->contactId->value];
    }
}
