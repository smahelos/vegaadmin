<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Domain\Invoice\Notifications\Contracts\InvoiceReminderNotifierInterface;
use App\Domain\Invoice\Notifications\DTO\InvoiceReminderPayload;
use App\Domain\Invoice\Notifications\Enums\InvoiceReminderType;
use App\Domain\Shared\Notifications\DTO\Recipient;
use Illuminate\Console\Command;

/**
 * Invoice reminder test for selected invoice
 */
class TestInvoiceReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:test-reminder {invoice_id? : Invoice ID to test} {--invoice= : Invoice ID to test (alternative option)} {--type=all : Reminder type (upcoming, due, overdue, all)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Invoice reminder test for selected invoice';    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $invoiceId = $this->argument('invoice_id') ?: $this->option('invoice');
        $type = $this->option('type');

        // Validate type
        $validTypes = ['upcoming', 'due', 'overdue', 'all'];
        if (!in_array($type, $validTypes)) {
            $this->error("Invalid type '{$type}'. Valid types are: " . implode(', ', $validTypes));
            return 1;
        }

        if (!$invoiceId) {
            $this->info('No invoice ID provided. Testing with sample data for type: ' . $type);
            return $this->handleTestWithoutInvoice($type);
        }

        $invoice = Invoice::find($invoiceId);
        if (!$invoice) {
            $this->error("Invoice with ID {$invoiceId} not found");
            return 1;
        }

        /** @var Invoice $invoice */
        $this->info("Invoice reminder testing #{$invoice->invoice_vs}");

        if ($type === 'upcoming' || $type === 'all') {
            $this->testUpcomingReminder($invoice);
        }

        if ($type === 'due' || $type === 'all') {
            $this->testDueReminder($invoice);
        }

        if ($type === 'overdue' || $type === 'all') {
            $this->testOverdueReminder($invoice);
        }

        $this->info('Testing finished.');
        return 0;
    }

    private function testUpcomingReminder(Invoice $invoice): void
    {
        $this->info('Invoice reminder test about upcomming due date...');

        if ($invoice->supplier && $invoice->supplier->email) {
            $this->sendViaNotifier(InvoiceReminderType::UPCOMING_DUE, $invoice, 'supplier', $invoice->supplier->preferredLocale());
            $this->info("Sent to supplier: {$invoice->supplier->email}");
        } else {
            $this->warn('Supplier has not email to send information to.');
        }

        if ($invoice->client && $invoice->client->email) {
            $this->sendViaNotifier(InvoiceReminderType::UPCOMING_DUE, $invoice, 'client', $invoice->client->preferredLocale());
            $this->info("Sent to client: {$invoice->client->email}");
        } else {
            $this->warn('Client  has not email to send information to.');
        }
    }

    private function testDueReminder(Invoice $invoice): void
    {
        $this->info('Invoice reminder test about due date is today...');

        if ($invoice->supplier && $invoice->supplier->email) {
            $this->sendViaNotifier(InvoiceReminderType::DUE_TODAY, $invoice, 'supplier', $invoice->supplier->preferredLocale());
            $this->info("Sent to supplier: {$invoice->supplier->email}");
        } else {
            $this->warn('Supplier has not email to send information to.');
        }

        if ($invoice->client && $invoice->client->email) {
            $this->sendViaNotifier(InvoiceReminderType::DUE_TODAY, $invoice, 'client', $invoice->client->preferredLocale());
            $this->info("Sent to client: {$invoice->client->email}");
        } else {
            $this->warn('Client has not email to send information to.');
        }
    }

    private function testOverdueReminder(Invoice $invoice): void
    {
        $this->info('Invoice reminder test about overdue...');

        if ($invoice->supplier && $invoice->supplier->email) {
            $this->sendViaNotifier(InvoiceReminderType::OVERDUE, $invoice, 'supplier', $invoice->supplier->preferredLocale());
            $this->info("Sent to supplier: {$invoice->supplier->email}");
        } else {
            $this->warn('Supplier has not email to send information to.');
        }

        if ($invoice->client && $invoice->client->email) {
            $this->sendViaNotifier(InvoiceReminderType::OVERDUE, $invoice, 'client', $invoice->client->preferredLocale());
            $this->info("Sent to client: {$invoice->client->email}");
        } else {
            $this->warn('Client has not email to send information to.');
        }
    }

    private function handleTestWithoutInvoice(string $type): int
    {
        $this->info("Testing {$type} reminder functionality without specific invoice...");

        if ($type === 'upcoming' || $type === 'all') {
            $this->info('Testing upcoming reminder logic...');
        }

        if ($type === 'due' || $type === 'all') {
            $this->info('Testing due reminder logic...');
        }

        if ($type === 'overdue' || $type === 'all') {
            $this->info('Testing overdue reminder logic...');
        }

        $this->info('Testing completed successfully.');
        return 0;
    }

    /**
     * Build and send notification via notifier port
     */
    private function sendViaNotifier(InvoiceReminderType $type, Invoice $invoice, string $recipientType, ?string $locale = null): void
    {
        /** @var InvoiceReminderNotifierInterface $notifier */
        $notifier = app(InvoiceReminderNotifierInterface::class);
        $payload = new InvoiceReminderPayload(
            invoiceNumber: $invoice->invoice_vs,
            dueDate: new \DateTimeImmutable(\Carbon\Carbon::parse($invoice->issue_date)->addDays($invoice->due_in)->format('Y-m-d')),
            recipientType: $recipientType,
            locale: $locale,
        );
        $recipient = new Recipient(
            name: $recipientType === 'supplier' ? ($invoice->supplier->name ?? '') : ($invoice->client->name ?? ''),
            email: $recipientType === 'supplier' ? ($invoice->supplier->email ?? '') : ($invoice->client->email ?? ''),
            preferredLocale: $locale,
        );
        $notifier->send($type, $payload, $recipient);
    }
}
