<?php

declare(strict_types=1);

namespace App\Infrastructure\Notifications\Invoice\Messages;

use App\Domain\Invoice\Notifications\DTO\InvoiceReminderPayload;
use App\Domain\Invoice\Notifications\Enums\InvoiceReminderType;
use App\Infrastructure\Notifications\Invoice\InvoiceReminderMailFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class InvoiceDueReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly InvoiceReminderPayload $payload) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return app(InvoiceReminderMailFactory::class)->create(
            $this->payload,
            InvoiceReminderType::DUE_TODAY,
            $this->locale,
            $notifiable->name ?? '',
        );
    }
}
