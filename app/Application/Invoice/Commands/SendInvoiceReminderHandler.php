<?php

declare(strict_types=1);

namespace App\Application\Invoice\Commands;

use App\Application\Invoice\Support\InvoiceReminderSender;
use App\Domain\Invoice\Contracts\InvoiceReminderReadRepositoryInterface;
use App\Domain\Invoice\Exceptions\InvoiceNotFoundException;
use App\Domain\Shared\Authorization\ValueObjects\AllOwnersScope;
use App\Domain\Shared\Time\Contracts\ClockInterface;

final class SendInvoiceReminderHandler
{
    public function __construct(
        private readonly InvoiceReminderReadRepositoryInterface $invoices,
        private readonly InvoiceReminderSender $sender,
        private readonly ClockInterface $clock,
    ) {}

    public function handle(SendInvoiceReminder $command, AllOwnersScope $scope): void
    {
        $invoice = $this->invoices->findById($command->invoiceId, $scope);
        if ($invoice === null) {
            throw new InvoiceNotFoundException('Invoice not found');
        }
        $days = $invoice->dueDate->daysUntil($this->clock->now());
        foreach ($command->types as $type) {
            $this->sender->send(
                $invoice,
                $type,
                $days,
            );
        }
    }
}
