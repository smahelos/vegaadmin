<?php

declare(strict_types=1);

namespace App\Application\Invoice\Commands;

use App\Application\Invoice\DTO\InvoiceRemindersResult;
use App\Application\Invoice\Support\InvoiceReminderSender;
use App\Domain\Invoice\Contracts\InvoiceReminderReadRepositoryInterface;
use App\Domain\Invoice\Notifications\Enums\InvoiceReminderType;
use App\Domain\Shared\Authorization\ValueObjects\AllOwnersScope;
use App\Domain\Shared\Time\Contracts\ClockInterface;
use InvalidArgumentException;

final class SendInvoiceRemindersHandler
{
    public function __construct(
        private readonly InvoiceReminderReadRepositoryInterface $invoices,
        private readonly InvoiceReminderSender $sender,
        private readonly ClockInterface $clock,
    ) {}

    public function handle(SendInvoiceReminders $command, AllOwnersScope $scope): InvoiceRemindersResult
    {
        $daysBefore = $command->daysBefore;
        $daysAfter = $command->daysAfter;
        if ($daysBefore < 1 || $daysAfter < 1) {
            throw new InvalidArgumentException('Reminder offsets must be positive integers');
        }
        $today = $this->clock->now();
        $counts = array_fill_keys(
            array_column(InvoiceReminderType::cases(), 'value'),
            0,
        );
        foreach ($this->invoices->unpaidForReminders($scope) as $invoice) {
            $days = $invoice->dueDate->daysUntil($today);
            $type = match ($days) {
                $daysBefore => InvoiceReminderType::UPCOMING_DUE,
                0 => InvoiceReminderType::DUE_TODAY,
                -$daysAfter => InvoiceReminderType::OVERDUE,
                default => null,
            };
            if ($type === null) {
                continue;
            }
            $counts[$type->value] += $this->sender->send(
                $invoice,
                $type,
                $days,
            );
        }

        return new InvoiceRemindersResult(
            $counts[InvoiceReminderType::UPCOMING_DUE->value],
            $counts[InvoiceReminderType::DUE_TODAY->value],
            $counts[InvoiceReminderType::OVERDUE->value],
        );
    }
}
