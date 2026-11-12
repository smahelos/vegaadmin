<?php

declare(strict_types=1);

namespace App\Domain\Invoice\Contracts;

use App\Domain\Invoice\DTO\ReminderInvoiceDTO;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Shared\Authorization\ValueObjects\AllOwnersScope;

interface InvoiceReminderReadRepositoryInterface
{
    public function findById(InvoiceId $id, AllOwnersScope $scope): ?ReminderInvoiceDTO;

    /** @return iterable<ReminderInvoiceDTO> */
    public function unpaidForReminders(AllOwnersScope $scope): iterable;
}
