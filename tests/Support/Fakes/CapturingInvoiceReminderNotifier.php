<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use App\Domain\Invoice\Notifications\Contracts\InvoiceReminderNotifierInterface;
use App\Domain\Invoice\Notifications\DTO\InvoiceReminderPayload;
use App\Domain\Invoice\Notifications\Enums\InvoiceReminderType;
use App\Domain\Shared\Notifications\DTO\Recipient;

final class CapturingInvoiceReminderNotifier implements InvoiceReminderNotifierInterface
{
    /** @var list<array{InvoiceReminderType, InvoiceReminderPayload, Recipient}> */
    public array $sent = [];

    public function send(
        InvoiceReminderType $type,
        InvoiceReminderPayload $payload,
        Recipient $recipient,
    ): void {
        $this->sent[] = [
            $type,
            $payload,
            $recipient,
        ];
    }
}
