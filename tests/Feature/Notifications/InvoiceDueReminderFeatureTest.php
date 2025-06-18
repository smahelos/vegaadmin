<?php

namespace Tests\Feature\Notifications;

use App\Models\Invoice;
use App\Models\User;
use App\Notifications\InvoiceDueReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
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

        $notification = new InvoiceDueReminder($this->invoice, 'supplier');
        
        $this->user->notify($notification);

        Notification::assertSentTo($this->user, InvoiceDueReminder::class);
    }

    #[Test]
    public function notification_can_be_sent_to_anonymous_notifiable(): void
    {
        Notification::fake();

        $notification = new InvoiceDueReminder($this->invoice, 'client');
        
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

        $notification = new InvoiceDueReminder($this->invoice, 'supplier');
        
        $this->user->notify($notification);

        Notification::assertSentTo($this->user, InvoiceDueReminder::class, function ($notification) {
            return $notification instanceof \Illuminate\Contracts\Queue\ShouldQueue;
        });
    }

    #[Test]
    public function notification_contains_correct_invoice_data(): void
    {
        Notification::fake();

        $notification = new InvoiceDueReminder($this->invoice, 'supplier');
        
        $this->user->notify($notification);

        Notification::assertSentTo($this->user, InvoiceDueReminder::class, function ($notification) {
            // Access invoice using reflection since it's protected
            $reflection = new \ReflectionClass($notification);
            $invoiceProperty = $reflection->getProperty('invoice');
            $invoiceProperty->setAccessible(true);
            $invoice = $invoiceProperty->getValue($notification);
            
            return $invoice->invoice_vs === $this->invoice->invoice_vs;
        });
    }

    #[Test]
    public function notification_sets_correct_recipient_type(): void
    {
        Notification::fake();

        $notification = new InvoiceDueReminder($this->invoice, 'client');
        
        $this->user->notify($notification);

        Notification::assertSentTo($this->user, InvoiceDueReminder::class, function ($notification) {
            // Access recipientType using reflection since it's protected
            $reflection = new \ReflectionClass($notification);
            $recipientTypeProperty = $reflection->getProperty('recipientType');
            $recipientTypeProperty->setAccessible(true);
            $recipientType = $recipientTypeProperty->getValue($notification);
            
            return $recipientType === 'client';
        });
    }

    #[Test]
    public function mail_message_has_correct_subject(): void
    {
        $notification = new InvoiceDueReminder($this->invoice, 'supplier');
        
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
        $notification = new InvoiceDueReminder($this->invoice, 'client');
        
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
        $notification = new InvoiceDueReminder($this->invoice, 'supplier');
        
        $mailMessage = $notification->toMail($this->user);

        // Use reflection to access the view property
        $reflection = new \ReflectionClass($mailMessage);
        $viewProperty = $reflection->getProperty('view');
        $viewProperty->setAccessible(true);
        $view = $viewProperty->getValue($mailMessage);

        $this->assertEquals('emails.invoices.reminders.due_today_supplier', $view);
    }

    #[Test]
    public function mail_message_includes_invoice_in_view_data(): void
    {
        $notification = new InvoiceDueReminder($this->invoice, 'supplier');
        
        $mailMessage = $notification->toMail($this->user);

        // Use reflection to access the viewData property
        $reflection = new \ReflectionClass($mailMessage);
        $viewDataProperty = $reflection->getProperty('viewData');
        $viewDataProperty->setAccessible(true);
        $viewData = $viewDataProperty->getValue($mailMessage);

        $this->assertArrayHasKey('invoice', $viewData);
        $this->assertEquals($this->invoice->id, $viewData['invoice']->id);
        $this->assertEquals($this->invoice->invoice_vs, $viewData['invoice']->invoice_vs);
    }

    #[Test]
    public function mail_message_includes_formatted_due_date(): void
    {
        $notification = new InvoiceDueReminder($this->invoice, 'supplier');
        
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
        $notification = new InvoiceDueReminder($this->invoice, 'supplier');
        
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
        $notification = new InvoiceDueReminder($this->invoice, 'supplier');
        
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
        // Create a mock user with preferred locale
        $userWithLocale = $this->createMock(User::class);
        $userWithLocale->method('preferredLocale')->willReturn('cs');
        $userWithLocale->name = 'Czech User';

        App::shouldReceive('setLocale')->once()->with('cs');

        $notification = new InvoiceDueReminder($this->invoice, 'supplier');
        $notification->toMail($userWithLocale);
    }

    #[Test]
    public function notification_falls_back_to_default_locale(): void
    {
        // Create a mock user without preferred locale
        $userWithoutLocale = $this->createMock(User::class);
        $userWithoutLocale->method('preferredLocale')->willReturn(null);
        $userWithoutLocale->name = 'Default Locale User';

        App::shouldReceive('setLocale')->once()->with('en');

        $notification = new InvoiceDueReminder($this->invoice, 'supplier');
        $notification->toMail($userWithoutLocale);
    }

    #[Test]
    public function notification_can_be_sent_with_different_recipient_types(): void
    {
        Notification::fake();

        // Test with client recipient type
        $clientNotification = new InvoiceDueReminder($this->invoice, 'client');
        $this->user->notify($clientNotification);

        // Test with supplier recipient type  
        $supplierNotification = new InvoiceDueReminder($this->invoice, 'supplier');
        $this->user->notify($supplierNotification);

        Notification::assertSentTo($this->user, InvoiceDueReminder::class, function ($notification) {
            $reflection = new \ReflectionClass($notification);
            $recipientTypeProperty = $reflection->getProperty('recipientType');
            $recipientTypeProperty->setAccessible(true);
            $recipientType = $recipientTypeProperty->getValue($notification);
            
            return in_array($recipientType, ['client', 'supplier']);
        });

        // Verify both notifications were sent
        Notification::assertSentToTimes($this->user, InvoiceDueReminder::class, 2);
    }
}
