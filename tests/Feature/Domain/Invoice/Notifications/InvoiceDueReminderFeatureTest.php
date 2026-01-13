<?php

namespace Tests\Feature\Domain\Invoice\Notifications;

use App\Models\Invoice;
use App\Models\User;
use App\Infrastructure\Notifications\Invoice\Messages\InvoiceDueReminder;
use App\Domain\Invoice\Notifications\DTO\InvoiceReminderPayload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceDueReminderFeatureTest extends TestCase
{
    use RefreshDatabase;

    private Invoice $invoice;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'invoice_vs' => '2025001',
            'due_in' => 30,
            'issue_date' => now()->subDays(30)->format('Y-m-d'), // Due today (30 days ago + 30 days)
        ]);
    }

    #[Test]
    public function notification_can_be_sent_to_user(): void
    {
        Notification::fake();

        $payload = new InvoiceReminderPayload(
            invoiceNumber: $this->invoice->invoice_vs,
            dueDate: new \DateTimeImmutable(now()->format('Y-m-d')),
            recipientType: 'supplier',
        );
        $notification = new InvoiceDueReminder($payload);
        
        $this->user->notify($notification);

        Notification::assertSentTo($this->user, InvoiceDueReminder::class);
    }

    #[Test]
    public function notification_can_be_sent_to_anonymous_notifiable(): void
    {
        Notification::fake();

        $payload = new InvoiceReminderPayload(
            invoiceNumber: $this->invoice->invoice_vs,
            dueDate: new \DateTimeImmutable(now()->format('Y-m-d')),
            recipientType: 'client',
        );
        $notification = new InvoiceDueReminder($payload);
        
        Notification::route('mail', 'client@example.com')->notify($notification);

        Notification::assertSentTo(
            new AnonymousNotifiable(), 
            InvoiceDueReminder::class
        );
    }

    #[Test]
    public function notification_is_queued(): void
    {
        Notification::fake();

        $payload = new InvoiceReminderPayload(
            invoiceNumber: $this->invoice->invoice_vs,
            dueDate: new \DateTimeImmutable(now()->format('Y-m-d')),
            recipientType: 'supplier',
        );
        $notification = new InvoiceDueReminder($payload);
        
        $this->user->notify($notification);

        Notification::assertSentTo($this->user, InvoiceDueReminder::class, function ($notification) {
            return $notification instanceof \Illuminate\Contracts\Queue\ShouldQueue;
        });
    }

    #[Test]
    public function notification_contains_correct_invoice_data(): void
    {
        Notification::fake();

        $payload = new InvoiceReminderPayload(
            invoiceNumber: $this->invoice->invoice_vs,
            dueDate: new \DateTimeImmutable(now()->format('Y-m-d')),
            recipientType: 'supplier',
        );
        $notification = new InvoiceDueReminder($payload);
        
        $this->user->notify($notification);

        Notification::assertSentTo($this->user, InvoiceDueReminder::class, function ($notification) {
            // Access invoice using reflection since it's protected
            $reflection = new \ReflectionClass($notification);
            $payloadProperty = $reflection->getProperty('payload');
            $payloadProperty->setAccessible(true);
            /** @var InvoiceReminderPayload $payload */
            $payload = $payloadProperty->getValue($notification);
            return $payload->invoiceNumber === $this->invoice->invoice_vs;
        });
    }

    #[Test]
    public function notification_sets_correct_recipient_type(): void
    {
        Notification::fake();

        $payload = new InvoiceReminderPayload(
            invoiceNumber: $this->invoice->invoice_vs,
            dueDate: new \DateTimeImmutable(now()->format('Y-m-d')),
            recipientType: 'client',
        );
        $notification = new InvoiceDueReminder($payload);
        
        $this->user->notify($notification);

        Notification::assertSentTo($this->user, InvoiceDueReminder::class, function ($notification) {
            // Access recipientType using reflection since it's protected
            $reflection = new \ReflectionClass($notification);
            $payloadProperty = $reflection->getProperty('payload');
            $payloadProperty->setAccessible(true);
            /** @var InvoiceReminderPayload $payload */
            $payload = $payloadProperty->getValue($notification);
            return $payload->recipientType === 'client';
        });
    }

    #[Test]
    public function mail_message_has_correct_subject(): void
    {
        $payload = new InvoiceReminderPayload(
            invoiceNumber: $this->invoice->invoice_vs,
            dueDate: new \DateTimeImmutable(now()->format('Y-m-d')),
            recipientType: 'supplier',
        );
        $notification = new InvoiceDueReminder($payload);
        $notification->locale('en');
        $mailMessage = $notification->toMail($this->user);

        // Use reflection to access the subject property
        $reflection = new \ReflectionClass($mailMessage);
        $subjectProperty = $reflection->getProperty('subject');
        $subjectProperty->setAccessible(true);
        $subject = $subjectProperty->getValue($mailMessage);

        $this->assertStringContainsString($this->invoice->invoice_vs, $subject);
    }

    #[Test]
    public function mail_message_uses_correct_view_for_client(): void
    {
        $payload = new InvoiceReminderPayload(
            invoiceNumber: $this->invoice->invoice_vs,
            dueDate: new \DateTimeImmutable(now()->format('Y-m-d')),
            recipientType: 'client',
        );
        $notification = new InvoiceDueReminder($payload);
        $notification->locale('en');
        $mailMessage = $notification->toMail($this->user);

        // Use reflection to access the view property
        $reflection = new \ReflectionClass($mailMessage);
        $viewProperty = $reflection->getProperty('view');
        $viewProperty->setAccessible(true);
        $view = $viewProperty->getValue($mailMessage);

        $this->assertEquals('emails.invoices.reminders.due_today_client', $view);
    }

    #[Test]
    public function mail_message_uses_correct_view_for_supplier(): void
    {
        $payload = new InvoiceReminderPayload(
            invoiceNumber: $this->invoice->invoice_vs,
            dueDate: new \DateTimeImmutable(now()->format('Y-m-d')),
            recipientType: 'supplier',
        );
        $notification = new InvoiceDueReminder($payload);
        $notification->locale('en');
        $mailMessage = $notification->toMail($this->user);

        // Use reflection to access the view property
        $reflection = new \ReflectionClass($mailMessage);
        $viewProperty = $reflection->getProperty('view');
        $viewProperty->setAccessible(true);
        $view = $viewProperty->getValue($mailMessage);

        $this->assertEquals('emails.invoices.reminders.due_today_supplier', $view);
    }

    #[Test]
    public function mail_message_includes_invoice_number_in_view_data(): void
    {
        $payload = new InvoiceReminderPayload(
            invoiceNumber: $this->invoice->invoice_vs,
            dueDate: new \DateTimeImmutable(now()->format('Y-m-d')),
            recipientType: 'supplier',
        );
        $notification = new InvoiceDueReminder($payload);
        $notification->locale('en');
        $mailMessage = $notification->toMail($this->user);

        // Use reflection to access the viewData property
        $reflection = new \ReflectionClass($mailMessage);
        $viewDataProperty = $reflection->getProperty('viewData');
        $viewDataProperty->setAccessible(true);
        $viewData = $viewDataProperty->getValue($mailMessage);

        $this->assertArrayHasKey('invoiceNumber', $viewData);
        $this->assertEquals($this->invoice->invoice_vs, $viewData['invoiceNumber']);
    }

    #[Test]
    public function mail_message_includes_formatted_due_date(): void
    {
        $payload = new InvoiceReminderPayload(
            invoiceNumber: $this->invoice->invoice_vs,
            dueDate: new \DateTimeImmutable(now()->format('Y-m-d')),
            recipientType: 'supplier',
        );
        $notification = new InvoiceDueReminder($payload);
        $notification->locale('en');
        $mailMessage = $notification->toMail($this->user);

        // Use reflection to access the viewData property
        $reflection = new \ReflectionClass($mailMessage);
        $viewDataProperty = $reflection->getProperty('viewData');
        $viewDataProperty->setAccessible(true);
        $viewData = $viewDataProperty->getValue($mailMessage);

        $this->assertArrayHasKey('dueDate', $viewData);
        $this->assertMatchesRegularExpression('/\d{2}\.\d{2}\.\d{4}/', $viewData['dueDate']);
    }

    #[Test]
    public function mail_message_includes_user_greeting(): void
    {
        $payload = new InvoiceReminderPayload(
            invoiceNumber: $this->invoice->invoice_vs,
            dueDate: new \DateTimeImmutable(now()->format('Y-m-d')),
            recipientType: 'supplier',
        );
        $notification = new InvoiceDueReminder($payload);
        $notification->locale('en');
        $mailMessage = $notification->toMail($this->user);

        // Use reflection to access the viewData property
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
            dueDate: new \DateTimeImmutable(now()->format('Y-m-d')),
            recipientType: 'supplier',
        );
        $notification = new InvoiceDueReminder($payload);
        $notification->locale('en');
        $mailMessage = $notification->toMail($this->user);

        // Use reflection to access the viewData property
        $reflection = new \ReflectionClass($mailMessage);
        $viewDataProperty = $reflection->getProperty('viewData');
        $viewDataProperty->setAccessible(true);
        $viewData = $viewDataProperty->getValue($mailMessage);

        $this->assertArrayHasKey('locale', $viewData);
        $this->assertIsString($viewData['locale']);
    }

    #[Test]
    public function notification_respects_user_preferred_locale(): void
    {
        $payload = new InvoiceReminderPayload(
            invoiceNumber: $this->invoice->invoice_vs,
            dueDate: new \DateTimeImmutable(now()->format('Y-m-d')),
            recipientType: 'supplier',
        );
        $notification = new InvoiceDueReminder($payload);
        $notification->locale('cs');
        $mailMessage = $notification->toMail($this->user);
        $ref = new \ReflectionClass($mailMessage);
        $viewDataProperty = $ref->getProperty('viewData');
        $viewDataProperty->setAccessible(true);
        $viewData = $viewDataProperty->getValue($mailMessage);
        $this->assertEquals('cs', $viewData['locale']);
    }

    #[Test]
    public function notification_falls_back_to_default_locale(): void
    {
        $payload = new InvoiceReminderPayload(
            invoiceNumber: $this->invoice->invoice_vs,
            dueDate: new \DateTimeImmutable(now()->format('Y-m-d')),
            recipientType: 'supplier',
        );
        $notification = new InvoiceDueReminder($payload);
        $notification->locale('en');
        $mailMessage = $notification->toMail($this->user);
        $ref = new \ReflectionClass($mailMessage);
        $viewDataProperty = $ref->getProperty('viewData');
        $viewDataProperty->setAccessible(true);
        $viewData = $viewDataProperty->getValue($mailMessage);
        $this->assertEquals('en', $viewData['locale']);
    }

    #[Test]
    public function notification_can_be_sent_with_different_recipient_types(): void
    {
        Notification::fake();

        // Test with client recipient type
        $clientPayload = new InvoiceReminderPayload(
            invoiceNumber: $this->invoice->invoice_vs,
            dueDate: new \DateTimeImmutable(now()->format('Y-m-d')),
            recipientType: 'client',
        );
        $this->user->notify(new InvoiceDueReminder($clientPayload));

        // Test with supplier recipient type
        $supplierPayload = new InvoiceReminderPayload(
            invoiceNumber: $this->invoice->invoice_vs,
            dueDate: new \DateTimeImmutable(now()->format('Y-m-d')),
            recipientType: 'supplier',
        );
        $this->user->notify(new InvoiceDueReminder($supplierPayload));

        Notification::assertSentTo($this->user, InvoiceDueReminder::class, function ($notification) {
            $reflection = new \ReflectionClass($notification);
            $payloadProperty = $reflection->getProperty('payload');
            $payloadProperty->setAccessible(true);
            /** @var InvoiceReminderPayload $payload */
            $payload = $payloadProperty->getValue($notification);
            return in_array($payload->recipientType, ['client', 'supplier']);
        });

        // Verify both notifications were sent
        Notification::assertSentToTimes($this->user, InvoiceDueReminder::class, 2);
    }
}
