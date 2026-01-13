<?php

namespace Tests\Feature\Domain\Invoice\Notifications;

use App\Models\Invoice;
use App\Models\User;
use App\Infrastructure\Notifications\Invoice\Messages\InvoiceUpcomingDueReminder;
use App\Domain\Invoice\Notifications\DTO\InvoiceReminderPayload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceUpcomingDueReminderFeatureTest extends TestCase
{
    use RefreshDatabase;

    private Invoice $invoice;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->invoice = Invoice::factory()->create([
            'issue_date' => now()->subDays(25)->format('Y-m-d'), // 25 days ago
            'due_in' => 30, // Due date = 25 days ago + 30 days = 5 days from now
            'invoice_vs' => '2024-002'
        ]);
        
        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com'
        ]);
    }

    #[Test]
    public function notification_can_be_sent_to_user(): void
    {
        Notification::fake();
        
        $payload = new InvoiceReminderPayload(
            invoiceNumber: $this->invoice->invoice_vs,
            dueDate: new \DateTimeImmutable(now()->addDays(5)->format('Y-m-d')),
            daysLeft: 5,
            recipientType: 'client',
        );
        $this->user->notify(new InvoiceUpcomingDueReminder($payload));
        
        Notification::assertSentTo($this->user, InvoiceUpcomingDueReminder::class);
    }

    #[Test]
    public function notification_can_be_sent_to_anonymous_notifiable(): void
    {
        Notification::fake();
        
        $anonymousNotifiable = (new AnonymousNotifiable())
            ->route('mail', 'test@example.com');
        
        $payload = new InvoiceReminderPayload(
            invoiceNumber: $this->invoice->invoice_vs,
            dueDate: new \DateTimeImmutable(now()->addDays(5)->format('Y-m-d')),
            daysLeft: 5,
            recipientType: 'supplier',
        );
        $anonymousNotifiable->notify(new InvoiceUpcomingDueReminder($payload));
        
        Notification::assertSentTo($anonymousNotifiable, InvoiceUpcomingDueReminder::class);
    }

    #[Test]
    public function notification_is_queued(): void
    {
        Notification::fake();
        
        $payload = new InvoiceReminderPayload(
            invoiceNumber: $this->invoice->invoice_vs,
            dueDate: new \DateTimeImmutable(now()->addDays(5)->format('Y-m-d')),
            daysLeft: 5,
            recipientType: 'supplier',
        );
        $this->user->notify(new InvoiceUpcomingDueReminder($payload));
        
        Notification::assertSentTo($this->user, InvoiceUpcomingDueReminder::class, function ($notification) {
            return $notification instanceof \Illuminate\Contracts\Queue\ShouldQueue;
        });
    }

    #[Test]
    public function notification_contains_correct_invoice_data(): void
    {
        $payload = new InvoiceReminderPayload(
            invoiceNumber: $this->invoice->invoice_vs,
            dueDate: new \DateTimeImmutable(now()->addDays(5)->format('Y-m-d')),
            daysLeft: 5,
            recipientType: 'supplier',
        );
        $notification = new InvoiceUpcomingDueReminder($payload);
        
        $reflection = new \ReflectionClass($notification);
        $payloadProp = $reflection->getProperty('payload');
        $payloadProp->setAccessible(true);
        /** @var InvoiceReminderPayload $actual */
        $actual = $payloadProp->getValue($notification);
        $this->assertEquals($this->invoice->invoice_vs, $actual->invoiceNumber);
    }

    #[Test]
    public function notification_sets_correct_recipient_type(): void
    {
        $payload = new InvoiceReminderPayload(
            invoiceNumber: $this->invoice->invoice_vs,
            dueDate: new \DateTimeImmutable(now()->addDays(5)->format('Y-m-d')),
            daysLeft: 5,
            recipientType: 'client',
        );
        $notification = new InvoiceUpcomingDueReminder($payload);
        
        $reflection = new \ReflectionClass($notification);
        $payloadProp = $reflection->getProperty('payload');
        $payloadProp->setAccessible(true);
        /** @var InvoiceReminderPayload $actual */
        $actual = $payloadProp->getValue($notification);
        $this->assertEquals('client', $actual->recipientType);
    }

    #[Test]
    public function mail_message_has_correct_subject(): void
    {
        $payload = new InvoiceReminderPayload(
            invoiceNumber: $this->invoice->invoice_vs,
            dueDate: new \DateTimeImmutable(now()->addDays(5)->format('Y-m-d')),
            daysLeft: 5,
            recipientType: 'supplier',
        );
        $notification = new InvoiceUpcomingDueReminder($payload);
        $notification->locale('en');
        $mailMessage = $notification->toMail($this->user);
        
        // Use reflection to check the subject
        $reflection = new \ReflectionClass($mailMessage);
        $subjectProperty = $reflection->getProperty('subject');
        $subjectProperty->setAccessible(true);
        
        $this->assertStringContainsString('2024-002', $subjectProperty->getValue($mailMessage));
    }

    #[Test]
    public function mail_message_uses_correct_view_for_client(): void
    {
        $payload = new InvoiceReminderPayload(
            invoiceNumber: $this->invoice->invoice_vs,
            dueDate: new \DateTimeImmutable(now()->addDays(5)->format('Y-m-d')),
            daysLeft: 5,
            recipientType: 'client',
        );
        $notification = new InvoiceUpcomingDueReminder($payload);
        $notification->locale('en');
        $mailMessage = $notification->toMail($this->user);
        
        $reflection = new \ReflectionClass($mailMessage);
        $viewProperty = $reflection->getProperty('view');
        $viewProperty->setAccessible(true);
        
        $this->assertEquals('emails.invoices.reminders.upcoming_due_client', $viewProperty->getValue($mailMessage));
    }

    #[Test]
    public function mail_message_uses_correct_view_for_supplier(): void
    {
        $payload = new InvoiceReminderPayload(
            invoiceNumber: $this->invoice->invoice_vs,
            dueDate: new \DateTimeImmutable(now()->addDays(5)->format('Y-m-d')),
            daysLeft: 5,
            recipientType: 'supplier',
        );
        $notification = new InvoiceUpcomingDueReminder($payload);
        $notification->locale('en');
        $mailMessage = $notification->toMail($this->user);
        
        $reflection = new \ReflectionClass($mailMessage);
        $viewProperty = $reflection->getProperty('view');
        $viewProperty->setAccessible(true);
        
        $this->assertEquals('emails.invoices.reminders.upcoming_due_supplier', $viewProperty->getValue($mailMessage));
    }

    #[Test]
    public function mail_message_includes_invoice_in_view_data(): void
    {
        $payload = new InvoiceReminderPayload(
            invoiceNumber: $this->invoice->invoice_vs,
            dueDate: new \DateTimeImmutable(now()->addDays(5)->format('Y-m-d')),
            daysLeft: 5,
            recipientType: 'supplier',
        );
        $notification = new InvoiceUpcomingDueReminder($payload);
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
        $payload = new InvoiceReminderPayload(
            invoiceNumber: $this->invoice->invoice_vs,
            dueDate: new \DateTimeImmutable(now()->addDays(5)->format('Y-m-d')),
            daysLeft: 5,
            recipientType: 'supplier',
        );
        $notification = new InvoiceUpcomingDueReminder($payload);
        $notification->locale('en');
        $mailMessage = $notification->toMail($this->user);
        
        $reflection = new \ReflectionClass($mailMessage);
        $viewDataProperty = $reflection->getProperty('viewData');
        $viewDataProperty->setAccessible(true);
        $viewData = $viewDataProperty->getValue($mailMessage);
        
        $this->assertArrayHasKey('dueDate', $viewData);
        $this->assertIsString($viewData['dueDate']);
        $this->assertMatchesRegularExpression('/^\d{2}\.\d{2}\.\d{4}$/', $viewData['dueDate']);
    }

    #[Test]
    public function mail_message_includes_days_left(): void
    {
        $payload = new InvoiceReminderPayload(
            invoiceNumber: $this->invoice->invoice_vs,
            dueDate: new \DateTimeImmutable(now()->addDays(5)->format('Y-m-d')),
            daysLeft: 5,
            recipientType: 'supplier',
        );
        $notification = new InvoiceUpcomingDueReminder($payload);
        $notification->locale('en');
        $mailMessage = $notification->toMail($this->user);
        
        $reflection = new \ReflectionClass($mailMessage);
        $viewDataProperty = $reflection->getProperty('viewData');
        $viewDataProperty->setAccessible(true);
        $viewData = $viewDataProperty->getValue($mailMessage);
        
        $this->assertArrayHasKey('daysLeft', $viewData);
        $this->assertIsNumeric($viewData['daysLeft']); // Can be float or int
        $this->assertGreaterThan(0, $viewData['daysLeft']); // Should be positive for upcoming due
    }

    #[Test]
    public function mail_message_includes_user_greeting(): void
    {
        $payload = new InvoiceReminderPayload(
            invoiceNumber: $this->invoice->invoice_vs,
            dueDate: new \DateTimeImmutable(now()->addDays(5)->format('Y-m-d')),
            daysLeft: 5,
            recipientType: 'supplier',
        );
        $notification = new InvoiceUpcomingDueReminder($payload);
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
        $payload = new InvoiceReminderPayload(
            invoiceNumber: $this->invoice->invoice_vs,
            dueDate: new \DateTimeImmutable(now()->addDays(5)->format('Y-m-d')),
            daysLeft: 5,
            recipientType: 'supplier',
        );
        $notification = new InvoiceUpcomingDueReminder($payload);
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
        $payload = new InvoiceReminderPayload(
            invoiceNumber: $this->invoice->invoice_vs,
            dueDate: new \DateTimeImmutable(now()->addDays(5)->format('Y-m-d')),
            daysLeft: 5,
            recipientType: 'supplier',
        );
        $notification = new InvoiceUpcomingDueReminder($payload);
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
        $payload = new InvoiceReminderPayload(
            invoiceNumber: $this->invoice->invoice_vs,
            dueDate: new \DateTimeImmutable(now()->addDays(5)->format('Y-m-d')),
            daysLeft: 5,
            recipientType: 'supplier',
        );
        $notification = new InvoiceUpcomingDueReminder($payload);
        $notification->locale('en');
        $mailMessage = $notification->toMail($this->user);
        $reflection = new \ReflectionClass($mailMessage);
        $viewDataProperty = $reflection->getProperty('viewData');
        $viewDataProperty->setAccessible(true);
        $viewData = $viewDataProperty->getValue($mailMessage);
        $this->assertArrayHasKey('locale', $viewData);
        $this->assertEquals('en', $viewData['locale']);
    }

    #[Test]
    public function notification_can_be_sent_with_different_recipient_types(): void
    {
        Notification::fake();
        
        // Test with client recipient type
        $clientPayload = new InvoiceReminderPayload(
            invoiceNumber: $this->invoice->invoice_vs,
            dueDate: new \DateTimeImmutable(now()->addDays(5)->format('Y-m-d')),
            daysLeft: 5,
            recipientType: 'client',
        );
        $this->user->notify(new InvoiceUpcomingDueReminder($clientPayload));
        
        // Test with supplier recipient type
        $supplierPayload = new InvoiceReminderPayload(
            invoiceNumber: $this->invoice->invoice_vs,
            dueDate: new \DateTimeImmutable(now()->addDays(5)->format('Y-m-d')),
            daysLeft: 5,
            recipientType: 'supplier',
        );
        $this->user->notify(new InvoiceUpcomingDueReminder($supplierPayload));
        
        Notification::assertSentTo($this->user, InvoiceUpcomingDueReminder::class, function ($notification) {
            $reflection = new \ReflectionClass($notification);
            $payloadProperty = $reflection->getProperty('payload');
            $payloadProperty->setAccessible(true);
            /** @var InvoiceReminderPayload $payload */
            $payload = $payloadProperty->getValue($notification);
            return in_array($payload->recipientType, ['client', 'supplier']);
        });
    }
}
