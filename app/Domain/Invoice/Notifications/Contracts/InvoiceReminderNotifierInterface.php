<?php

namespace App\Domain\Invoice\Notifications\Contracts;

use App\Domain\Invoice\Notifications\DTO\InvoiceReminderPayload;
use App\Domain\Invoice\Notifications\Enums\InvoiceReminderType;
use App\Domain\Shared\Notifications\DTO\Recipient;

interface InvoiceReminderNotifierInterface
{
    /**
     * Send an invoice reminder notification using the given payload and recipient.
     */
    public function send(InvoiceReminderType $type, InvoiceReminderPayload $payload, Recipient $recipient): void;
}
