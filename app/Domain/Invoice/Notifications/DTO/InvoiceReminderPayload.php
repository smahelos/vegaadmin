<?php

declare(strict_types=1);

namespace App\Domain\Invoice\Notifications\DTO;

use DateTimeImmutable;

final readonly class InvoiceReminderPayload
{
    public function __construct(
        public string $invoiceNumber,
        public DateTimeImmutable $dueDate,
        public ?int $daysLeft = null,
        public ?int $daysOverdue = null,
        public string $recipientType = 'supplier',
        public ?string $locale = null,
        public string $recipientName = '',
    ) {}
}
