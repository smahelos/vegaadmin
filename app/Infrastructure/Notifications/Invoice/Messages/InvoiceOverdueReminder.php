<?php

declare(strict_types=1);

namespace App\Infrastructure\Notifications\Invoice\Messages;

use App\Domain\Invoice\Notifications\DTO\InvoiceReminderDTO;
use App\Domain\Invoice\Notifications\Enums\InvoiceReminderType;
use App\Infrastructure\Notifications\Invoice\InvoiceReminderMailFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class InvoiceOverdueReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly InvoiceReminderDTO $payload) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return app(InvoiceReminderMailFactory::class)->create(
            $this->payload,
            InvoiceReminderType::OVERDUE,
            $this->locale,
            $notifiable->name ?? '',
        );
    }
}
