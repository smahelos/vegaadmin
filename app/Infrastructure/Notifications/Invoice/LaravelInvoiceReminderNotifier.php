<?php

namespace App\Infrastructure\Notifications\Invoice;

use App\Domain\Invoice\Notifications\Contracts\InvoiceReminderNotifierInterface;
use App\Domain\Invoice\Notifications\DTO\InvoiceReminderPayload;
use App\Domain\Invoice\Notifications\Enums\InvoiceReminderType;
use App\Domain\Shared\Notifications\DTO\Recipient;
use App\Infrastructure\Notifications\Invoice\Messages\InvoiceDueReminder;
use App\Infrastructure\Notifications\Invoice\Messages\InvoiceOverdueReminder;
use App\Infrastructure\Notifications\Invoice\Messages\InvoiceUpcomingDueReminder;
use Illuminate\Support\Facades\Notification;

class LaravelInvoiceReminderNotifier implements InvoiceReminderNotifierInterface
{
    public function send(InvoiceReminderType $type, InvoiceReminderPayload $payload, Recipient $recipient): void
    {
        $notification = match ($type) {
            InvoiceReminderType::UPCOMING_DUE => new InvoiceUpcomingDueReminder($payload),
            InvoiceReminderType::DUE_TODAY => new InvoiceDueReminder($payload),
            InvoiceReminderType::OVERDUE => new InvoiceOverdueReminder($payload),
        };

        // Prefer notification locale scoping over global App::setLocale
        if ($payload->locale ?? $recipient->preferredLocale) {
            $notification->locale($payload->locale ?? $recipient->preferredLocale);
        }

        // Send via on-demand notification to email
        Notification::route('mail', $recipient->email)->notify($notification);
    }
}
