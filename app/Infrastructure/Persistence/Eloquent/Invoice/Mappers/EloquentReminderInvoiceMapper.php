<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Invoice\Mappers;

use App\Domain\Invoice\DTO\ReminderInvoiceDTO;
use App\Domain\Invoice\ValueObjects\InvoiceDueDate;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Infrastructure\Persistence\Eloquent\Party\Mappers\EloquentDocumentPartiesMapper;
use App\Models\Invoice;

final class EloquentReminderInvoiceMapper
{
    public static function toDTO(Invoice $invoice): ReminderInvoiceDTO
    {
        $parties = EloquentDocumentPartiesMapper::toDocumentParties($invoice);

        return new ReminderInvoiceDTO(
            InvoiceId::fromInt($invoice->id),
            Money::fromString((string) $invoice->payment_amount, $invoice->payment_currency),
            $invoice->invoice_vs,
            InvoiceDueDate::fromIssueDateAndDays($invoice->issue_date->toDateTimeImmutable(), $invoice->due_in),
            $parties->supplier,
            $parties->customer,
        );
    }
}
