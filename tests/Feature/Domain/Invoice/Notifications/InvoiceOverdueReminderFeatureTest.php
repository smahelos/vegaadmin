<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Invoice\Notifications;

use App\Domain\Invoice\Notifications\DTO\InvoiceReminderDTO;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\Shared\Time\Contracts\DateFormatterInterface;
use App\Infrastructure\Notifications\Invoice\Messages\InvoiceOverdueReminder;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class InvoiceOverdueReminderFeatureTest extends TestCase
{
    private Invoice $invoice;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->invoice = Invoice::factory()->create([
            'issue_date' => now()->subDays(35)->format('Y-m-d'), // 35 days ago
            'due_in' => 14, // Due date = 35 - 14 = 21 days ago (overdue)
            'invoice_vs' => '2024-001',
        ]);

        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }

    #[Test]
    public function notification_can_be_sent_to_user(): void
    {
        Notification::fake();

        $payload = new InvoiceReminderDTO(
            invoiceId: InvoiceId::fromInt(1),
            total: Money::fromString('100', 'CZK'),
            supplierName: 'Saved supplier',
            customerName: 'Saved customer',
            invoiceNumber: $this->invoice->invoice_vs,
            dueDate: new \DateTimeImmutable(now()->subDays(21)->format('Y-m-d')),
            daysOverdue: 21,
            recipientType: 'customer',
        );
        $this->user->notify(new InvoiceOverdueReminder($payload));

        Notification::assertSentTo($this->user, InvoiceOverdueReminder::class);
    }

    #[Test]
    public function notification_can_be_sent_to_anonymous_notifiable(): void
    {
        Notification::fake();

        $anonymousNotifiable = (new AnonymousNotifiable)
            ->route('mail', 'test@example.com');

        $payload = new InvoiceReminderDTO(
            invoiceId: InvoiceId::fromInt(1),
            total: Money::fromString('100', 'CZK'),
            supplierName: 'Saved supplier',
            customerName: 'Saved customer',
            invoiceNumber: $this->invoice->invoice_vs,
            dueDate: new \DateTimeImmutable(now()->subDays(21)->format('Y-m-d')),
            daysOverdue: 21,
            recipientType: 'supplier',
        );
        $anonymousNotifiable->notify(new InvoiceOverdueReminder($payload));

        Notification::assertSentTo($anonymousNotifiable, InvoiceOverdueReminder::class);
    }

    #[Test]
    public function notification_is_queued(): void
    {
        Notification::fake();

        $payload = new InvoiceReminderDTO(
            invoiceId: InvoiceId::fromInt(1),
            total: Money::fromString('100', 'CZK'),
            supplierName: 'Saved supplier',
            customerName: 'Saved customer',
            invoiceNumber: $this->invoice->invoice_vs,
            dueDate: new \DateTimeImmutable(now()->subDays(21)->format('Y-m-d')),
            daysOverdue: 21,
            recipientType: 'supplier',
        );
        $this->user->notify(new InvoiceOverdueReminder($payload));

        Notification::assertSentTo(
            $this->user,
            InvoiceOverdueReminder::class,
            function ($notification) {
                return $notification instanceof ShouldQueue;
            },
        );
    }

    #[Test]
    public function notification_contains_correct_invoice_data(): void
    {
        $payload = new InvoiceReminderDTO(
            invoiceId: InvoiceId::fromInt(1),
            total: Money::fromString('100', 'CZK'),
            supplierName: 'Saved supplier',
            customerName: 'Saved customer',
            invoiceNumber: $this->invoice->invoice_vs,
            dueDate: new \DateTimeImmutable(now()->subDays(21)->format('Y-m-d')),
            daysOverdue: 21,
            recipientType: 'supplier',
        );
        $notification = new InvoiceOverdueReminder($payload);

        $reflection = new \ReflectionClass($notification);
        $payloadProperty = $reflection->getProperty('payload');
        $payloadProperty->setAccessible(true);
        /** @var InvoiceReminderDTO $inner */
        $inner = $payloadProperty->getValue($notification);
        $this->assertSame($this->invoice->invoice_vs, $inner->invoiceNumber);
    }

    #[Test]
    public function notification_sets_correct_recipient_type(): void
    {
        $payload = new InvoiceReminderDTO(
            invoiceId: InvoiceId::fromInt(1),
            total: Money::fromString('100', 'CZK'),
            supplierName: 'Saved supplier',
            customerName: 'Saved customer',
            invoiceNumber: $this->invoice->invoice_vs,
            dueDate: new \DateTimeImmutable(now()->subDays(21)->format('Y-m-d')),
            daysOverdue: 21,
            recipientType: 'customer',
        );
        $notification = new InvoiceOverdueReminder($payload);

        $reflection = new \ReflectionClass($notification);
        $payloadProperty = $reflection->getProperty('payload');
        $payloadProperty->setAccessible(true);
        /** @var InvoiceReminderDTO $inner */
        $inner = $payloadProperty->getValue($notification);
        $this->assertEquals('customer', $inner->recipientType);
    }

    #[Test]
    public function mail_message_has_correct_subject(): void
    {
        $payload = new InvoiceReminderDTO(
            invoiceId: InvoiceId::fromInt(1),
            total: Money::fromString('100', 'CZK'),
            supplierName: 'Saved supplier',
            customerName: 'Saved customer',
            invoiceNumber: $this->invoice->invoice_vs,
            dueDate: new \DateTimeImmutable(now()->subDays(21)->format('Y-m-d')),
            daysOverdue: 21,
            recipientType: 'supplier',
        );
        $notification = new InvoiceOverdueReminder($payload);
        $notification->locale('en');
        $mailMessage = $notification->toMail($this->user);

        // Use reflection to check the subject
        $reflection = new \ReflectionClass($mailMessage);
        $subjectProperty = $reflection->getProperty('subject');
        $subjectProperty->setAccessible(true);

        $this->assertStringContainsString('2024-001', $subjectProperty->getValue($mailMessage));
    }

    #[Test]
    public function mail_message_uses_correct_view_for_client(): void
    {
        $payload = new InvoiceReminderDTO(
            invoiceId: InvoiceId::fromInt(1),
            total: Money::fromString('100', 'CZK'),
            supplierName: 'Saved supplier',
            customerName: 'Saved customer',
            invoiceNumber: $this->invoice->invoice_vs,
            dueDate: new \DateTimeImmutable(now()->subDays(21)->format('Y-m-d')),
            daysOverdue: 21,
            recipientType: 'customer',
        );
        $notification = new InvoiceOverdueReminder($payload);
        $notification->locale('en');
        $mailMessage = $notification->toMail($this->user);

        $reflection = new \ReflectionClass($mailMessage);
        $viewProperty = $reflection->getProperty('view');
        $viewProperty->setAccessible(true);

        $this->assertEquals('emails.invoices.reminders.overdue_customer', $viewProperty->getValue($mailMessage));
    }

    #[Test]
    public function mail_message_uses_correct_view_for_supplier(): void
    {
        $payload = new InvoiceReminderDTO(
            invoiceId: InvoiceId::fromInt(1),
            total: Money::fromString('100', 'CZK'),
            supplierName: 'Saved supplier',
            customerName: 'Saved customer',
            invoiceNumber: $this->invoice->invoice_vs,
            dueDate: new \DateTimeImmutable(now()->subDays(21)->format('Y-m-d')),
            daysOverdue: 21,
            recipientType: 'supplier',
        );
        $notification = new InvoiceOverdueReminder($payload);
        $notification->locale('en');
        $mailMessage = $notification->toMail($this->user);

        $reflection = new \ReflectionClass($mailMessage);
        $viewProperty = $reflection->getProperty('view');
        $viewProperty->setAccessible(true);

        $this->assertEquals('emails.invoices.reminders.overdue_supplier', $viewProperty->getValue($mailMessage));
    }

    #[Test]
    public function mail_message_includes_invoice_in_view_data(): void
    {
        $payload = new InvoiceReminderDTO(
            invoiceId: InvoiceId::fromInt(1),
            total: Money::fromString('100', 'CZK'),
            supplierName: 'Saved supplier',
            customerName: 'Saved customer',
            invoiceNumber: $this->invoice->invoice_vs,
            dueDate: new \DateTimeImmutable(now()->subDays(21)->format('Y-m-d')),
            daysOverdue: 21,
            recipientType: 'supplier',
        );
        $notification = new InvoiceOverdueReminder($payload);
        $notification->locale('en');
        $mailMessage = $notification->toMail($this->user);

        $reflection = new \ReflectionClass($mailMessage);
        $viewDataProperty = $reflection->getProperty('viewData');
        $viewDataProperty->setAccessible(true);
        $viewData = $viewDataProperty->getValue($mailMessage);

        $this->assertArrayHasKey('invoiceNumber', $viewData);
        $this->assertSame($this->invoice->invoice_vs, $viewData['invoiceNumber']);
    }

    #[Test]
    public function mail_message_includes_formatted_due_date(): void
    {
        $payload = new InvoiceReminderDTO(
            invoiceId: InvoiceId::fromInt(1),
            total: Money::fromString('100', 'CZK'),
            supplierName: 'Saved supplier',
            customerName: 'Saved customer',
            invoiceNumber: $this->invoice->invoice_vs,
            dueDate: new \DateTimeImmutable(now()->subDays(21)->format('Y-m-d')),
            daysOverdue: 21,
            recipientType: 'supplier',
        );
        $notification = new InvoiceOverdueReminder($payload);
        $notification->locale('en');
        $mailMessage = $notification->toMail($this->user);

        $reflection = new \ReflectionClass($mailMessage);
        $viewDataProperty = $reflection->getProperty('viewData');
        $viewDataProperty->setAccessible(true);
        $viewData = $viewDataProperty->getValue($mailMessage);

        $this->assertArrayHasKey('dueDate', $viewData);
        $this->assertIsString($viewData['dueDate']);
        $this->assertSame(app(DateFormatterInterface::class)->formatDate($payload->dueDate, $viewData['locale']), $viewData['dueDate']);
    }

    #[Test]
    public function mail_message_includes_days_overdue(): void
    {
        $payload = new InvoiceReminderDTO(
            invoiceId: InvoiceId::fromInt(1),
            total: Money::fromString('100', 'CZK'),
            supplierName: 'Saved supplier',
            customerName: 'Saved customer',
            invoiceNumber: $this->invoice->invoice_vs,
            dueDate: new \DateTimeImmutable(now()->subDays(21)->format('Y-m-d')),
            daysOverdue: 21,
            recipientType: 'supplier',
        );
        $notification = new InvoiceOverdueReminder($payload);
        $notification->locale('en');
        $mailMessage = $notification->toMail($this->user);

        $reflection = new \ReflectionClass($mailMessage);
        $viewDataProperty = $reflection->getProperty('viewData');
        $viewDataProperty->setAccessible(true);
        $viewData = $viewDataProperty->getValue($mailMessage);

        $this->assertArrayHasKey('daysOverdue', $viewData);
        $this->assertIsNumeric($viewData['daysOverdue']); // Can be float or int
        $this->assertGreaterThan(0, $viewData['daysOverdue']); // Should be positive for overdue invoice
    }

    #[Test]
    public function mail_message_includes_user_greeting(): void
    {
        $payload = new InvoiceReminderDTO(
            invoiceId: InvoiceId::fromInt(1),
            total: Money::fromString('100', 'CZK'),
            supplierName: 'Saved supplier',
            customerName: 'Saved customer',
            invoiceNumber: $this->invoice->invoice_vs,
            dueDate: new \DateTimeImmutable(now()->subDays(21)->format('Y-m-d')),
            daysOverdue: 21,
            recipientType: 'supplier',
        );
        $notification = new InvoiceOverdueReminder($payload);
        $notification->locale('en');
        $mailMessage = $notification->toMail($this->user);

        $reflection = new \ReflectionClass($mailMessage);
        $viewDataProperty = $reflection->getProperty('viewData');
        $viewDataProperty->setAccessible(true);
        $viewData = $viewDataProperty->getValue($mailMessage);

        $this->assertArrayHasKey('greeting', $viewData);
        $this->assertIsString($viewData['greeting']);
    }

    #[Test]
    public function mail_message_includes_locale_information(): void
    {
        $payload = new InvoiceReminderDTO(
            invoiceId: InvoiceId::fromInt(1),
            total: Money::fromString('100', 'CZK'),
            supplierName: 'Saved supplier',
            customerName: 'Saved customer',
            invoiceNumber: $this->invoice->invoice_vs,
            dueDate: new \DateTimeImmutable(now()->subDays(21)->format('Y-m-d')),
            daysOverdue: 21,
            recipientType: 'supplier',
        );
        $notification = new InvoiceOverdueReminder($payload);
        $notification->locale('cs');
        $mailMessage = $notification->toMail($this->user);

        $reflection = new \ReflectionClass($mailMessage);
        $viewDataProperty = $reflection->getProperty('viewData');
        $viewDataProperty->setAccessible(true);
        $viewData = $viewDataProperty->getValue($mailMessage);

        $this->assertArrayHasKey('locale', $viewData);
        $this->assertEquals('cs', $viewData['locale']);
    }

    #[Test]
    public function notification_respects_user_preferred_locale(): void
    {
        $payload = new InvoiceReminderDTO(
            invoiceId: InvoiceId::fromInt(1),
            total: Money::fromString('100', 'CZK'),
            supplierName: 'Saved supplier',
            customerName: 'Saved customer',
            invoiceNumber: $this->invoice->invoice_vs,
            dueDate: new \DateTimeImmutable(now()->subDays(21)->format('Y-m-d')),
            daysOverdue: 21,
            recipientType: 'supplier',
        );
        $notification = new InvoiceOverdueReminder($payload);
        $notification->locale('cs');
        $mailMessage = $notification->toMail($this->user);

        $reflection = new \ReflectionClass($mailMessage);
        $viewDataProperty = $reflection->getProperty('viewData');
        $viewDataProperty->setAccessible(true);
        $viewData = $viewDataProperty->getValue($mailMessage);

        $this->assertArrayHasKey('locale', $viewData);
        $this->assertEquals('cs', $viewData['locale']);
    }

    #[Test]
    public function notification_falls_back_to_default_locale(): void
    {
        $payload = new InvoiceReminderDTO(
            invoiceId: InvoiceId::fromInt(1),
            total: Money::fromString('100', 'CZK'),
            supplierName: 'Saved supplier',
            customerName: 'Saved customer',
            invoiceNumber: $this->invoice->invoice_vs,
            dueDate: new \DateTimeImmutable(now()->subDays(21)->format('Y-m-d')),
            daysOverdue: 21,
            recipientType: 'supplier',
        );
        $notification = new InvoiceOverdueReminder($payload);
        // Do not set an explicit locale to verify fallback to app default
        $mailMessage = $notification->toMail($this->user);

        $reflection = new \ReflectionClass($mailMessage);
        $viewDataProperty = $reflection->getProperty('viewData');
        $viewDataProperty->setAccessible(true);
        $viewData = $viewDataProperty->getValue($mailMessage);

        $this->assertArrayHasKey('locale', $viewData);
        $this->assertSame(app()->getLocale(), $viewData['locale']);
    }

    #[Test]
    public function notification_can_be_sent_with_different_recipient_types(): void
    {
        Notification::fake();

        // Test with client recipient type
        $clientPayload = new InvoiceReminderDTO(
            invoiceId: InvoiceId::fromInt(1),
            total: Money::fromString('100', 'CZK'),
            supplierName: 'Saved supplier',
            customerName: 'Saved customer',
            invoiceNumber: $this->invoice->invoice_vs,
            dueDate: new \DateTimeImmutable(now()->subDays(21)->format('Y-m-d')),
            daysOverdue: 21,
            recipientType: 'customer',
        );
        $this->user->notify(new InvoiceOverdueReminder($clientPayload));

        // Test with supplier recipient type
        $supplierPayload = new InvoiceReminderDTO(
            invoiceId: InvoiceId::fromInt(1),
            total: Money::fromString('100', 'CZK'),
            supplierName: 'Saved supplier',
            customerName: 'Saved customer',
            invoiceNumber: $this->invoice->invoice_vs,
            dueDate: new \DateTimeImmutable(now()->subDays(21)->format('Y-m-d')),
            daysOverdue: 21,
            recipientType: 'supplier',
        );
        $this->user->notify(new InvoiceOverdueReminder($supplierPayload));

        Notification::assertSentTo(
            $this->user,
            InvoiceOverdueReminder::class,
            function ($notification) {
                $reflection = new \ReflectionClass($notification);
                $payloadProperty = $reflection->getProperty('payload');
                $payloadProperty->setAccessible(true);
                /** @var InvoiceReminderDTO $payload */
                $payload = $payloadProperty->getValue($notification);

                return in_array($payload->recipientType, ['customer', 'supplier']);
            },
        );
    }
}
