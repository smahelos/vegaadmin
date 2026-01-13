<?php

namespace App\Domain\Invoice\Notifications\DTO;

class InvoiceReminderPayload
{
    public function __construct(
        public readonly string $invoiceNumber,
        public readonly \DateTimeImmutable $dueDate,
        public readonly ?int $daysLeft = null,
        public readonly ?int $daysOverdue = null,
        public readonly string $recipientType = 'supplier', // 'supplier'|'client'
        public readonly ?string $locale = null,
    ) {}
}
