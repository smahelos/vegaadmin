<?php

declare(strict_types=1);

namespace App\Infrastructure\Notifications\Invoice;

use App\Domain\Invoice\Notifications\DTO\InvoiceReminderDTO;
use App\Domain\Invoice\Notifications\Enums\InvoiceReminderType;
use App\Domain\Shared\Money\Contracts\MoneyFormatterInterface;
use App\Domain\Shared\Time\Contracts\DateFormatterInterface;
use Illuminate\Notifications\Messages\MailMessage;

final class InvoiceReminderMailFactory
{
    public function __construct(
        private readonly DateFormatterInterface $dates,
        private readonly MoneyFormatterInterface $money,
    ) {}

    public function create(
        InvoiceReminderDTO $payload,
        InvoiceReminderType $type,
        ?string $locale,
        string $fallbackName,
    ): MailMessage {
        $application = app();
        $locale ??= $payload->locale ?? $application->getLocale();
        $customer = $payload->recipientType === 'customer';
        $view = match ($type) {
            InvoiceReminderType::UPCOMING_DUE => $customer ? 'emails.invoices.reminders.upcoming_due_customer' : 'emails.invoices.reminders.upcoming_due_supplier',
            InvoiceReminderType::DUE_TODAY => $customer ? 'emails.invoices.reminders.due_today_customer' : 'emails.invoices.reminders.due_today_supplier',
            InvoiceReminderType::OVERDUE => $customer ? 'emails.invoices.reminders.overdue_customer' : 'emails.invoices.reminders.overdue_supplier',
        };

        $message = new MailMessage;
        $message->subject(
            __(
                'invoices.reminders.' . $type->value . '_subject',
                ['number' => $payload->invoiceNumber],
                $locale,
            ),
        );
        $message->view(
            $view,
            [
                'invoiceNumber' => $payload->invoiceNumber,
                'supplierName' => $payload->supplierName,
                'customerName' => $payload->customerName,
                'formattedAmount' => $this->money->formatWithCode($payload->total, $locale),
                'dueDate' => $this->dates->formatDate($payload->dueDate, $locale),
                'daysLeft' => $payload->daysLeft,
                'daysOverdue' => $payload->daysOverdue,
                'greeting' => __(
                    'invoices.reminders.greeting',
                    ['name' => $payload->recipientName !== '' ? $payload->recipientName : $fallbackName],
                    $locale,
                ),
                'locale' => $locale,
                'invoiceUrl' => route('frontend.invoice.show', ['id' => $payload->invoiceId->value, 'locale' => $locale]),
            ],
        );

        return $message;
    }
}
