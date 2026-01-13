<?php

namespace Tests\Feature\Infrastructure\Notifications;

use App\Domain\Invoice\Notifications\Contracts\InvoiceReminderNotifierInterface;
use App\Domain\Invoice\Notifications\DTO\InvoiceReminderPayload;
use App\Domain\Invoice\Notifications\Enums\InvoiceReminderType;
use App\Domain\Shared\Notifications\DTO\Recipient;
use App\Infrastructure\Notifications\Invoice\Messages\InvoiceDueReminder as InfraDue;
use App\Infrastructure\Notifications\Invoice\Messages\InvoiceOverdueReminder as InfraOverdue;
use App\Infrastructure\Notifications\Invoice\Messages\InvoiceUpcomingDueReminder as InfraUpcoming;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LaravelInvoiceReminderNotifierTest extends TestCase
{
    #[Test]
    public function sends_upcoming_due_via_on_demand_with_locale(): void
    {
        Notification::fake();

        $recipient = new Recipient(name: 'John Doe', email: 'john@example.com', preferredLocale: 'cs');
        $payload = new InvoiceReminderPayload(
            invoiceNumber: 'INV-1001',
            dueDate: new \DateTimeImmutable('2025-10-10'),
            daysLeft: 3,
            recipientType: 'client',
            locale: 'cs',
        );

        /** @var InvoiceReminderNotifierInterface $notifier */
        $notifier = app(InvoiceReminderNotifierInterface::class);
        $notifier->send(InvoiceReminderType::UPCOMING_DUE, $payload, $recipient);

        Notification::assertSentOnDemand(
            InfraUpcoming::class,
            function ($notification, $channels, $notifiable) use ($recipient) {
                // correct channel and email route
                $routeOk = in_array('mail', $channels, true) && ($notifiable->routes['mail'] ?? null) === $recipient->email;
                // locale is set on notification instance
                $ref = new \ReflectionClass($notification);
                $prop = $ref->getParentClass()->getProperty('locale'); // property on base Notification
                $prop->setAccessible(true);
                $locale = $prop->getValue($notification);
                return $routeOk && $locale === 'cs';
            }
        );
    }

    #[Test]
    public function sends_due_today_via_on_demand_with_locale(): void
    {
        Notification::fake();

        $recipient = new Recipient(name: 'Jane Doe', email: 'jane@example.com', preferredLocale: 'en');
        $payload = new InvoiceReminderPayload(
            invoiceNumber: 'INV-1002',
            dueDate: new \DateTimeImmutable('2025-10-07'),
            recipientType: 'supplier',
            locale: 'en',
        );

        $notifier = app(InvoiceReminderNotifierInterface::class);
        $notifier->send(InvoiceReminderType::DUE_TODAY, $payload, $recipient);

        Notification::assertSentOnDemand(
            InfraDue::class,
            function ($notification, $channels, $notifiable) use ($recipient) {
                $routeOk = in_array('mail', $channels, true) && ($notifiable->routes['mail'] ?? null) === $recipient->email;
                $ref = new \ReflectionClass($notification);
                $prop = $ref->getParentClass()->getProperty('locale');
                $prop->setAccessible(true);
                $locale = $prop->getValue($notification);
                return $routeOk && $locale === 'en';
            }
        );
    }

    #[Test]
    public function sends_overdue_via_on_demand_with_locale(): void
    {
        Notification::fake();

        $recipient = new Recipient(name: 'Max Mustermann', email: 'max@example.com', preferredLocale: 'de');
        $payload = new InvoiceReminderPayload(
            invoiceNumber: 'INV-1003',
            dueDate: new \DateTimeImmutable('2025-09-30'),
            daysOverdue: 7,
            recipientType: 'client',
            locale: 'de',
        );

        $notifier = app(InvoiceReminderNotifierInterface::class);
        $notifier->send(InvoiceReminderType::OVERDUE, $payload, $recipient);

        Notification::assertSentOnDemand(
            InfraOverdue::class,
            function ($notification, $channels, $notifiable) use ($recipient) {
                $routeOk = in_array('mail', $channels, true) && ($notifiable->routes['mail'] ?? null) === $recipient->email;
                $ref = new \ReflectionClass($notification);
                $prop = $ref->getParentClass()->getProperty('locale');
                $prop->setAccessible(true);
                $locale = $prop->getValue($notification);
                return $routeOk && $locale === 'de';
            }
        );
    }
}
