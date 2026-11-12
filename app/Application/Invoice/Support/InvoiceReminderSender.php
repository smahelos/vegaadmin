<?php

declare(strict_types=1);

namespace App\Application\Invoice\Support;

use App\Domain\Invoice\DTO\ReminderInvoiceDTO;
use App\Domain\Invoice\Notifications\Contracts\InvoiceReminderNotifierInterface;
use App\Domain\Invoice\Notifications\DTO\InvoiceReminderDTO;
use App\Domain\Invoice\Notifications\Enums\InvoiceReminderType;
use App\Domain\Shared\Notifications\DTO\Recipient;
use App\Domain\User\Contracts\LocaleServiceInterface;

final class InvoiceReminderSender
{
    public function __construct(
        private readonly InvoiceReminderNotifierInterface $notifier,
        private readonly LocaleServiceInterface $locales,
    ) {}

    /**
     * Sends the reminder to the supplier and the customer that have an email address.
     *
     * @return int how many reminders were sent
     */
    public function send(
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
                new InvoiceReminderDTO(
                    invoiceId: $invoice->id,
                    total: $invoice->total,
                    supplierName: $invoice->supplier->name,
                    customerName: $invoice->customer->name,
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
