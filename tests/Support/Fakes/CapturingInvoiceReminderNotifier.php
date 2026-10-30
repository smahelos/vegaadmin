<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use App\Domain\Invoice\Notifications\Contracts\InvoiceReminderNotifierInterface;
use App\Domain\Invoice\Notifications\DTO\InvoiceReminderDTO;
use App\Domain\Invoice\Notifications\Enums\InvoiceReminderType;
use App\Domain\Shared\Notifications\DTO\Recipient;

final class CapturingInvoiceReminderNotifier implements InvoiceReminderNotifierInterface
{
    /** @var list<array{InvoiceReminderType, InvoiceReminderDTO, Recipient}> */
    public array $sent = [];

    public function send(
        InvoiceReminderType $type,
        InvoiceReminderDTO $payload,
        Recipient $recipient,
    ): void {
        $this->sent[] = [
            $type,
            $payload,
            $recipient,
        ];
    }
}
