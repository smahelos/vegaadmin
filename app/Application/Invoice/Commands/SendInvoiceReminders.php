<?php

declare(strict_types=1);

namespace App\Application\Invoice\Commands;

use App\Application\Invoice\DTO\InvoiceRemindersResult;
use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\CallsOutsideDatabaseInterface;
use App\Application\Shared\UseCase\Contracts\CommandInterface;

/**
 * The scheduled reminder run over every owner's unpaid invoices, started by the cron task. It runs without a
 * transaction, so a failing invoice does not roll back the reminders already queued for the others.
 *
 * @implements CommandInterface<InvoiceRemindersResult>
 */
final readonly class SendInvoiceReminders implements CallsOutsideDatabaseInterface, CommandInterface
{
    public function __construct(
        public int $daysBefore,
        public int $daysAfter,
    ) {}

    public static function access(): Access
    {
        return Access::system();
    }
}
