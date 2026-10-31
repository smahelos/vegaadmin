<?php

declare(strict_types=1);

namespace App\Domain\Invoice\Events;

use App\Domain\Invoice\ValueObjects\ExpenseId;
use App\Domain\Shared\Events\Contracts\DomainEvent;
use App\Domain\User\ValueObjects\UserId;

final readonly class ExpenseCreated implements DomainEvent
{
    private string $eventId;

    public function __construct(public ExpenseId $expenseId, public UserId $userId)
    {
        $this->eventId = bin2hex(random_bytes(16));
    }

    public function getEventName(): string
    {
        return self::class;
    }

    public function getEventId(): string
    {
        return $this->eventId;
    }

    /** @return array<string, int> */
    public function getPayload(): array
    {
        return ['expense_id' => $this->expenseId->value, 'user_id' => $this->userId->toInt()];
    }
}
