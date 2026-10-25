<?php

namespace Tests\Feature\Console\Commands;

use App\Domain\Invoice\Enums\InvoiceStatus;
use App\Domain\Invoice\Notifications\Contracts\InvoiceReminderNotifierInterface;
use App\Domain\Invoice\Notifications\DTO\InvoiceReminderPayload;
use App\Domain\Invoice\Notifications\Enums\InvoiceReminderType;
use App\Domain\Shared\Notifications\DTO\Recipient;
use App\Infrastructure\Notifications\Invoice\Messages\InvoiceDueReminder;
use App\Infrastructure\Notifications\Invoice\Messages\InvoiceOverdueReminder;
use App\Infrastructure\Notifications\Invoice\Messages\InvoiceUpcomingDueReminder;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Status;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;
use Tests\Traits\CreatesFrontendTestEnvironment;

class CheckInvoicePaymentStatusFeatureTest extends TestCase
{
    use CreatesFrontendTestEnvironment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpFrontendTestEnvironment();

        Notification::fake();
    }

    #[Test]
    public function command_executes_successfully(): void
    {
        $exitCode = Artisan::call('invoices:check-payment-status');

        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function command_accepts_options(): void
    {
        $exitCode = Artisan::call('invoices:check-payment-status', [
            '--days-before' => 5,
            '--days-after' => 2,
        ]);

        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function command_sends_upcoming_due_reminders(): void
    {
        // Create client and invoice
        $client = Client::factory()->create([
            'email' => 'test@example.com',
        ]);

        $unpaidStatus = Status::where('slug', InvoiceStatus::PENDING->value)->firstOrFail();

        // Create invoice due in 3 days (default days-before)
        $invoice = Invoice::factory()->create([
            'client_id' => $client->id,
            'payment_status_id' => $unpaidStatus->id,
            'issue_date' => Carbon::today()->subDays(7), // Issued 7 days ago
            'due_in' => 10, // Due in 10 days from issue = 3 days from today
        ]);

        // Run the command
        $exitCode = Artisan::call('invoices:check-payment-status');

        $this->assertEquals(0, $exitCode);

        // Check that upcoming due reminder was sent via on-demand notification to client's email
        Notification::assertSentOnDemand(
            InvoiceUpcomingDueReminder::class,
            function (
                $notification,
                $channels,
                $notifiable,
            ) use ($client) {
                return in_array(
                    'mail',
                    $channels,
                    true,
                )
                    && ($notifiable->routes['mail'] ?? null) === $client->email;
            },
        );
    }

    #[Test]
    public function command_sends_due_today_reminders(): void
    {
        // Create client and invoice
        $client = Client::factory()->create([
            'email' => 'test@example.com',
        ]);

        $unpaidStatus = Status::where('slug', InvoiceStatus::PENDING->value)->firstOrFail();

        // Create invoice due today
        $invoice = Invoice::factory()->create([
            'client_id' => $client->id,
            'payment_status_id' => $unpaidStatus->id,
            'issue_date' => Carbon::today()->subDays(30), // Issued 30 days ago
            'due_in' => 30, // Due in 30 days from issue = today
        ]);

        // Run the command
        $exitCode = Artisan::call('invoices:check-payment-status');

        $this->assertEquals(0, $exitCode);

        // Check that due today reminder was sent via on-demand notification to client's email
        Notification::assertSentOnDemand(
            InvoiceDueReminder::class,
            function (
                $notification,
                $channels,
                $notifiable,
            ) use ($client) {
                return in_array(
                    'mail',
                    $channels,
                    true,
                )
                    && ($notifiable->routes['mail'] ?? null) === $client->email;
            },
        );
    }

    #[Test]
    public function command_sends_overdue_reminders(): void
    {
        // Create client and invoice
        $client = Client::factory()->create([
            'email' => 'test@example.com',
        ]);

        $unpaidStatus = Status::where('slug', InvoiceStatus::PARTIALLY_PAID->value)->firstOrFail();

        // Create invoice overdue by 1 day (default days-after)
        $invoice = Invoice::factory()->create([
            'client_id' => $client->id,
            'payment_status_id' => $unpaidStatus->id,
            'issue_date' => Carbon::today()->subDays(32), // Issued 32 days ago
            'due_in' => 31, // Due in 31 days from issue = 1 day overdue
        ]);

        // Run the command
        $exitCode = Artisan::call('invoices:check-payment-status');

        $this->assertEquals(0, $exitCode);

        // Being overdue is derived from the due date; the reminder leaves the stored status alone
        $this->assertSame($unpaidStatus->id, $invoice->fresh()->payment_status_id);

        // Check that overdue reminder was sent via on-demand notification to client's email
        Notification::assertSentOnDemand(
            InvoiceOverdueReminder::class,
            function (
                $notification,
                $channels,
                $notifiable,
            ) use ($client) {
                return in_array(
                    'mail',
                    $channels,
                    true,
                )
                    && ($notifiable->routes['mail'] ?? null) === $client->email;
            },
        );
    }

    #[Test]
    public function command_fails_when_a_reminder_cannot_be_sent(): void
    {
        $this->app->instance(InvoiceReminderNotifierInterface::class, new class implements InvoiceReminderNotifierInterface
        {
            public function send(
                InvoiceReminderType $type,
                InvoiceReminderPayload $payload,
                Recipient $recipient,
            ): void {
                throw new RuntimeException('Mail transport is down');
            }
        });
        Invoice::factory()->create([
            'client_id' => Client::factory()->create(['email' => 'test@example.com'])->id,
            'payment_status_id' => Status::where('slug', InvoiceStatus::PENDING->value)->firstOrFail()->id,
            'issue_date' => Carbon::today()->subDays(30),
            'due_in' => 30,
        ]);

        $exitCode = Artisan::call('invoices:check-payment-status');

        $this->assertSame(1, $exitCode);
    }

    #[Test]
    public function command_ignores_paid_invoices(): void
    {
        // Create client and invoice
        $client = Client::factory()->create([
            'email' => 'test@example.com',
        ]);

        $paidStatus = Status::where('slug', 'paid')->first();

        // Create paid invoice that would be overdue
        $invoice = Invoice::factory()->create([
            'client_id' => $client->id,
            'payment_status_id' => $paidStatus->id,
            'issue_date' => Carbon::today()->subDays(32),
            'due_in' => 31,
        ]);

        // Run the command
        $exitCode = Artisan::call('invoices:check-payment-status');

        $this->assertEquals(0, $exitCode);

        // Check that no reminders were sent
        Notification::assertNothingSent();
    }

    #[Test]
    public function command_ignores_cancelled_invoices(): void
    {
        // Create client and invoice
        $client = Client::factory()->create([
            'email' => 'test@example.com',
        ]);

        $cancelledStatus = Status::where('slug', 'cancelled')->first();

        // Create cancelled invoice that would be overdue
        $invoice = Invoice::factory()->create([
            'client_id' => $client->id,
            'payment_status_id' => $cancelledStatus->id,
            'issue_date' => Carbon::today()->subDays(32),
            'due_in' => 31,
        ]);

        // Run the command
        $exitCode = Artisan::call('invoices:check-payment-status');

        $this->assertEquals(0, $exitCode);

        // Check that no reminders were sent
        Notification::assertNothingSent();
    }

    #[Test]
    public function command_handles_custom_parameters(): void
    {
        // Create client and invoice
        $client = Client::factory()->create([
            'email' => 'test@example.com',
        ]);

        $unpaidStatus = Status::where('slug', InvoiceStatus::PENDING->value)->firstOrFail();

        // Create invoice due in 5 days
        $invoice = Invoice::factory()->create([
            'client_id' => $client->id,
            'payment_status_id' => $unpaidStatus->id,
            'issue_date' => Carbon::today()->subDays(5), // Issued 5 days ago
            'due_in' => 10, // Due in 10 days from issue = 5 days from today
        ]);

        // Run the command with custom days-before = 5
        $exitCode = Artisan::call('invoices:check-payment-status', [
            '--days-before' => 5,
        ]);

        $this->assertEquals(0, $exitCode);

        // Check that upcoming due reminder was sent via on-demand notification to client's email
        Notification::assertSentOnDemand(
            InvoiceUpcomingDueReminder::class,
            function (
                $notification,
                $channels,
                $notifiable,
            ) use ($client) {
                return in_array(
                    'mail',
                    $channels,
                    true,
                )
                    && ($notifiable->routes['mail'] ?? null) === $client->email;
            },
        );
    }

    #[Test]
    public function command_provides_feedback(): void
    {
        Artisan::call('invoices:check-payment-status');

        $output = Artisan::output();

        $this->assertStringContainsString('Checking invoice for payment reminder', $output);
    }

    #[Test]
    public function command_handles_no_invoices_gracefully(): void
    {
        // Run command with no invoices in database
        $exitCode = Artisan::call('invoices:check-payment-status');

        $this->assertEquals(0, $exitCode);

        // Should not send any notifications
        Notification::assertNothingSent();
    }
}
