<?php

namespace Tests\Feature\Notifications;

use App\Models\Invoice;
use App\Models\User;
use App\Notifications\InvoiceOverdueReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceOverdueReminderFeatureTest extends TestCase
{
    use RefreshDatabase;

    private Invoice $invoice;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->invoice = Invoice::factory()->create([
            'issue_date' => now()->subDays(35)->format('Y-m-d'), // 35 days ago
            'due_in' => 14, // Due date = 35 - 14 = 21 days ago (overdue)
            'invoice_vs' => '2024-001'
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
        
        $this->user->notify(new InvoiceOverdueReminder($this->invoice, 'client'));
        
        Notification::assertSentTo($this->user, InvoiceOverdueReminder::class);
    }

    #[Test]
    public function notification_can_be_sent_to_anonymous_notifiable(): void
    {
        Notification::fake();
        
        $anonymousNotifiable = (new AnonymousNotifiable())
            ->route('mail', 'test@example.com');
        
        $anonymousNotifiable->notify(new InvoiceOverdueReminder($this->invoice));
        
        Notification::assertSentTo($anonymousNotifiable, InvoiceOverdueReminder::class);
    }

    #[Test]
    public function notification_is_queued(): void
    {
        Notification::fake();
        
        $this->user->notify(new InvoiceOverdueReminder($this->invoice));
        
        Notification::assertSentTo($this->user, InvoiceOverdueReminder::class, function ($notification) {
            return $notification instanceof \Illuminate\Contracts\Queue\ShouldQueue;
        });
    }

    #[Test]
    public function notification_contains_correct_invoice_data(): void
    {
        $notification = new InvoiceOverdueReminder($this->invoice, 'supplier');
        
        $reflection = new \ReflectionClass($notification);
        $invoiceProperty = $reflection->getProperty('invoice');
        $invoiceProperty->setAccessible(true);
        
        $this->assertSame($this->invoice, $invoiceProperty->getValue($notification));
    }

    #[Test]
    public function notification_sets_correct_recipient_type(): void
    {
        $notification = new InvoiceOverdueReminder($this->invoice, 'client');
        
        $reflection = new \ReflectionClass($notification);
        $recipientTypeProperty = $reflection->getProperty('recipientType');
        $recipientTypeProperty->setAccessible(true);
        
        $this->assertEquals('client', $recipientTypeProperty->getValue($notification));
    }

    #[Test]
    public function mail_message_has_correct_subject(): void
    {
        App::shouldReceive('setLocale')->once()->with('en');
        
        $notification = new InvoiceOverdueReminder($this->invoice);
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
        App::shouldReceive('setLocale')->once()->with('en');
        
        $notification = new InvoiceOverdueReminder($this->invoice, 'client');
        $mailMessage = $notification->toMail($this->user);
        
        $reflection = new \ReflectionClass($mailMessage);
        $viewProperty = $reflection->getProperty('view');
        $viewProperty->setAccessible(true);
        
        $this->assertEquals('emails.invoices.reminders.overdue_client', $viewProperty->getValue($mailMessage));
    }

    #[Test]
    public function mail_message_uses_correct_view_for_supplier(): void
    {
        App::shouldReceive('setLocale')->once()->with('en');
        
        $notification = new InvoiceOverdueReminder($this->invoice, 'supplier');
        $mailMessage = $notification->toMail($this->user);
        
        $reflection = new \ReflectionClass($mailMessage);
        $viewProperty = $reflection->getProperty('view');
        $viewProperty->setAccessible(true);
        
        $this->assertEquals('emails.invoices.reminders.overdue_supplier', $viewProperty->getValue($mailMessage));
    }

    #[Test]
    public function mail_message_includes_invoice_in_view_data(): void
    {
        App::shouldReceive('setLocale')->once()->with('en');
        
        $notification = new InvoiceOverdueReminder($this->invoice);
        $mailMessage = $notification->toMail($this->user);
        
        $reflection = new \ReflectionClass($mailMessage);
        $viewDataProperty = $reflection->getProperty('viewData');
        $viewDataProperty->setAccessible(true);
        $viewData = $viewDataProperty->getValue($mailMessage);
        
        $this->assertArrayHasKey('invoice', $viewData);
        $this->assertSame($this->invoice, $viewData['invoice']);
    }

    #[Test]
    public function mail_message_includes_formatted_due_date(): void
    {
        App::shouldReceive('setLocale')->once()->with('en');
        
        $notification = new InvoiceOverdueReminder($this->invoice);
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
    public function mail_message_includes_days_overdue(): void
    {
        App::shouldReceive('setLocale')->once()->with('en');
        
        $notification = new InvoiceOverdueReminder($this->invoice);
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
        App::shouldReceive('setLocale')->once()->with('en');
        
        $notification = new InvoiceOverdueReminder($this->invoice);
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
        App::shouldReceive('setLocale')->once()->with('cs');
        
        // Create a mock user with Czech locale
        $userWithLocale = $this->createPartialMock(User::class, ['preferredLocale']);
        $userWithLocale->method('preferredLocale')->willReturn('cs');
        $userWithLocale->name = 'Test User';
        
        $notification = new InvoiceOverdueReminder($this->invoice);
        $mailMessage = $notification->toMail($userWithLocale);
        
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
        App::shouldReceive('setLocale')->once()->with('cs');
        
        // Create a mock user with preferred locale
        $userWithLocale = $this->createPartialMock(User::class, ['preferredLocale']);
        $userWithLocale->method('preferredLocale')->willReturn('cs');
        $userWithLocale->name = 'Test User';
        
        $notification = new InvoiceOverdueReminder($this->invoice);
        $notification->toMail($userWithLocale);
    }

    #[Test]
    public function notification_falls_back_to_default_locale(): void
    {
        App::shouldReceive('setLocale')->once()->with('en');
        
        // Create a mock user without preferred locale
        $userWithoutLocale = $this->createPartialMock(User::class, ['preferredLocale']);
        $userWithoutLocale->method('preferredLocale')->willReturn(null);
        $userWithoutLocale->name = 'Test User';
        
        $notification = new InvoiceOverdueReminder($this->invoice);
        $notification->toMail($userWithoutLocale);
    }

    #[Test]
    public function notification_can_be_sent_with_different_recipient_types(): void
    {
        Notification::fake();
        
        // Test with client recipient type
        $clientNotification = new InvoiceOverdueReminder($this->invoice, 'client');
        $this->user->notify($clientNotification);
        
        // Test with supplier recipient type
        $supplierNotification = new InvoiceOverdueReminder($this->invoice, 'supplier');
        $this->user->notify($supplierNotification);
        
        Notification::assertSentTo($this->user, InvoiceOverdueReminder::class, function ($notification) {
            $reflection = new \ReflectionClass($notification);
            $recipientTypeProperty = $reflection->getProperty('recipientType');
            $recipientTypeProperty->setAccessible(true);
            
            return in_array($recipientTypeProperty->getValue($notification), ['client', 'supplier']);
        });
    }
}
