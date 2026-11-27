<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Invoice\Repositories;

use App\Domain\Invoice\Contracts\InvoiceReminderReadRepositoryInterface;
use App\Domain\Invoice\DTO\ReminderInvoiceDTO;
use App\Domain\Invoice\Enums\InvoiceStatus;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Shared\Authorization\ValueObjects\AllOwnersScope;
use App\Infrastructure\Persistence\Eloquent\Invoice\Mappers\EloquentReminderInvoiceMapper;
use App\Models\Invoice;

final class EloquentInvoiceReminderRepository implements InvoiceReminderReadRepositoryInterface
{
    public function findById(InvoiceId $id, AllOwnersScope $scope): ?ReminderInvoiceDTO
    {
        $query = Invoice::query();
        // A draft has no due date and is never sent to the customer
        $query->whereNotNull('issued_at');
        $invoice = $query->find($id->value);

        return $invoice === null ? null : EloquentReminderInvoiceMapper::toDTO($invoice);
    }

    public function unpaidForReminders(AllOwnersScope $scope): iterable
    {
        $statuses = array_map(static fn (InvoiceStatus $status): string => $status->value, InvoiceStatus::unpaid());
        $query = Invoice::query();
        $query->whereNotNull('issued_at');
        $query->whereIn('payment_status', $statuses);
        $invoices = $query->lazyById();

        foreach ($invoices as $invoice) {
            yield EloquentReminderInvoiceMapper::toDTO($invoice);
        }
    }
}
