<?php

declare(strict_types=1);

namespace App\Domain\Invoice\Contracts;

use App\Domain\Invoice\DTO\ReminderInvoiceDTO;
use App\Domain\Invoice\ValueObjects\InvoiceId;

interface InvoiceReminderReadRepositoryInterface
{
    public function findByIdAny(InvoiceId $id): ?ReminderInvoiceDTO;

    /** @return iterable<ReminderInvoiceDTO> */
    public function unpaidForReminders(): iterable;
}
