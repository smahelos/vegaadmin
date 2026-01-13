<?php

namespace App\Infrastructure\Notifications\Invoice\Messages;

use App\Domain\Invoice\Notifications\DTO\InvoiceReminderPayload;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceUpcomingDueReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly InvoiceReminderPayload $payload) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $dueDate = $this->payload->dueDate->format('d.m.Y');
        $viewName = $this->payload->recipientType === 'client'
            ? 'emails.invoices.reminders.upcoming_due_client'
            : 'emails.invoices.reminders.upcoming_due_supplier';

        return (new MailMessage)
            ->subject(__('invoices.reminders.upcoming_due_subject', ['number' => $this->payload->invoiceNumber]))
            ->view($viewName, [
                'invoiceNumber' => $this->payload->invoiceNumber,
                'dueDate' => $dueDate,
                'daysLeft' => $this->payload->daysLeft,
                'greeting' => __('invoices.reminders.greeting', ['name' => $notifiable->name ?? '']),
                'locale' => $this->locale,
            ]);
    }
}
