<?php

declare(strict_types=1);

namespace App\Application\Invoice\Services;

use App\Application\Invoice\DTO\InvoiceRemindersResult;
use App\Domain\Invoice\Contracts\InvoiceReminderReadRepositoryInterface;
use App\Domain\Invoice\DTO\ReminderInvoiceDTO;
use App\Domain\Invoice\Exceptions\InvoiceNotFoundException;
use App\Domain\Invoice\Notifications\Contracts\InvoiceReminderNotifierInterface;
use App\Domain\Invoice\Notifications\DTO\InvoiceReminderPayload;
use App\Domain\Invoice\Notifications\Enums\InvoiceReminderType;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Shared\Notifications\DTO\Recipient;
use App\Domain\Shared\Time\Contracts\ClockInterface;
use App\Domain\User\Contracts\LocaleServiceInterface;
use InvalidArgumentException;

final class InvoiceReminderApplicationService
{
    public function __construct(
        private readonly InvoiceReminderReadRepositoryInterface $invoices,
        private readonly InvoiceReminderNotifierInterface $notifier,
        private readonly ClockInterface $clock,
        private readonly LocaleServiceInterface $locales,
    ) {}

    public function sendReminders(int $daysBefore, int $daysAfter): InvoiceRemindersResult
    {
        if ($daysBefore < 1 || $daysAfter < 1) {
            throw new InvalidArgumentException('Reminder offsets must be positive integers');
        }
        $today = $this->clock->now();
        $counts = array_fill_keys(array_column(InvoiceReminderType::cases(), 'value'), 0);
        foreach ($this->invoices->unpaidForReminders() as $invoice) {
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
            $counts[$type->value] += $this->sendInvoice(
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

    /** @param list<InvoiceReminderType> $types */
    public function sendForInvoice(InvoiceId $id, array $types): void
    {
        $invoice = $this->invoices->findByIdAny($id) ?? throw new InvoiceNotFoundException('Invoice not found');
        $days = $invoice->dueDate->daysUntil($this->clock->now());
        foreach ($types as $type) {
            $this->sendInvoice(
                $invoice,
                $type,
                $days,
            );
        }
    }

    private function sendInvoice(
        ReminderInvoiceDTO $invoice,
        InvoiceReminderType $type,
        int $days,
    ): int {
        $sent = 0;
        foreach (['supplier' => $invoice->supplier, 'customer' => $invoice->customer] as $role => $party) {
            if ($party->email === null || trim($party->email) === '') {
                continue;
            }
            $locale = $this->locales->localeFromCountry($party->address?->country->value);
            $this->notifier->send(
                $type,
                new InvoiceReminderPayload(
                    invoiceNumber: $invoice->number,
                    dueDate: $invoice->dueDate->date(),
                    daysLeft: $type === InvoiceReminderType::UPCOMING_DUE ? max(0, $days) : null,
                    daysOverdue: $type === InvoiceReminderType::OVERDUE ? max(0, -$days) : null,
                    recipientType: $role,
                    locale: $locale,
                    recipientName: $party->name,
                ),
                new Recipient(
                    $party->name,
                    $party->email,
                    $locale,
                ),
            );
            $sent++;
        }

        return $sent;
    }
}
