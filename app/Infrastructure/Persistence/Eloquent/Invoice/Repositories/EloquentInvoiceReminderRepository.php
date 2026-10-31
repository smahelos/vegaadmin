<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Invoice\Repositories;

use App\Domain\Invoice\Contracts\InvoiceReminderReadRepositoryInterface;
use App\Domain\Invoice\DTO\ReminderInvoiceDTO;
use App\Domain\Invoice\Enums\InvoiceStatus;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Infrastructure\Persistence\Eloquent\Invoice\Mappers\EloquentReminderInvoiceMapper;
use App\Models\Invoice;

final class EloquentInvoiceReminderRepository implements InvoiceReminderReadRepositoryInterface
{
    public function findByIdAny(InvoiceId $id): ?ReminderInvoiceDTO
    {
        $invoice = Invoice::query()->find($id->getValue());

        return $invoice === null ? null : EloquentReminderInvoiceMapper::toDTO($invoice);
    }

    public function unpaidForReminders(): iterable
    {
        $statuses = array_map(static fn (InvoiceStatus $status): string => $status->value, InvoiceStatus::unpaid());
        $invoices = Invoice::query()->whereHas('paymentStatus', static function ($query) use ($statuses): void {
            $query->whereIn('slug', $statuses);
        })->lazyById();

        foreach ($invoices as $invoice) {
            yield EloquentReminderInvoiceMapper::toDTO($invoice);
        }
    }
}
