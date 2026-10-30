<?php

declare(strict_types=1);

namespace App\Domain\Invoice\Notifications\DTO;

use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Shared\Money\ValueObjects\Money;
use DateTimeImmutable;

final readonly class InvoiceReminderDTO
{
    public function __construct(
        public InvoiceId $invoiceId,
        public Money $total,
        public string $supplierName,
        public string $customerName,
        public string $invoiceNumber,
        public DateTimeImmutable $dueDate,
        public ?int $daysLeft = null,
        public ?int $daysOverdue = null,
        public string $recipientType = 'supplier',
        public ?string $locale = null,
        public string $recipientName = '',
    ) {}
}
