<?php

declare(strict_types=1);

namespace App\Domain\Invoice\Enums;

/**
 * Payment status of an invoice, stored in `invoices.payment_status`. Being overdue is not a status: it follows
 * from an unpaid status and a past due date.
 */
enum InvoiceStatus: string
{
    case PAID = 'paid';
    case PENDING = 'pending';
    case CANCELLED = 'cancelled';

    /**
     * The client still owes money: reminders are sent and the invoice turns overdue after its due date.
     */
    public function isUnpaid(): bool
    {
        return $this === self::PENDING;
    }

    /**
     * @return list<self>
     */
    public static function unpaid(): array
    {
        return array_values(
            array_filter(self::cases(), static fn (self $status): bool => $status->isUnpaid()),
        );
    }
}
