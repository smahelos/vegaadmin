<?php

namespace App\Console\Commands;

use App\Domain\Invoice\Enums\InvoiceStatus;
use App\Domain\Invoice\Notifications\Contracts\InvoiceReminderNotifierInterface;
use App\Domain\Invoice\Notifications\DTO\InvoiceReminderPayload;
use App\Domain\Invoice\Notifications\Enums\InvoiceReminderType;
use App\Domain\Invoice\ValueObjects\InvoiceDueDate;
use App\Domain\Shared\Notifications\DTO\Recipient;
use App\Domain\Shared\Time\Contracts\ClockInterface;
use App\Models\Invoice;
use DateTimeImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckInvoicePaymentStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:check-payment-status {--days-before=3 : Days before due date to send upcoming reminder} {--days-after=1 : Days after due date to send overdue reminder}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check invoice payment status and send reminders based on due dates';

    /**
     * Execute the console command.
     */
    public function handle(ClockInterface $clock)
    {
        $daysBefore = (int) $this->option('days-before');
        $daysAfter = (int) $this->option('days-after');
        $today = $clock->now();
        $count = [
            'upcoming' => 0,
            'due_today' => 0,
            'overdue' => 0,
        ];

        $unpaidStatuses = array_map(
            static fn (InvoiceStatus $status): string => $status->value,
            InvoiceStatus::unpaid(),
        );

        $this->info('Checking invoice for payment reminder...');

        try {
            // 1. Invoices that are due in the next X days
            // Get all unpaid invoices and filter by due date in PHP (database-agnostic)
            $upcomingDueInvoices = Invoice::whereHas('paymentStatus', function ($query) use ($unpaidStatuses): void {
                $query->whereIn('slug', $unpaidStatuses);
            })
                ->get()
                ->filter(fn (Invoice $invoice): bool => $this->dueDate($invoice)->daysUntil($today) === $daysBefore);

            foreach ($upcomingDueInvoices as $invoice) {
                $this->sendUpcomingReminder($invoice, $today);
                $count['upcoming']++;
            }

            // 2. invoices that are due today
            $dueTodayInvoices = Invoice::whereHas('paymentStatus', function ($query) use ($unpaidStatuses): void {
                $query->whereIn('slug', $unpaidStatuses);
            })
                ->get()
                ->filter(fn (Invoice $invoice): bool => $this->dueDate($invoice)->daysUntil($today) === 0);

            foreach ($dueTodayInvoices as $invoice) {
                $this->sendDueTodayReminder($invoice);
                $count['due_today']++;
            }

            // 3. Invoices that are overdue (e.g. 1 day after due date)
            $overdueInvoices = Invoice::whereHas('paymentStatus', function ($query) use ($unpaidStatuses): void {
                $query->whereIn('slug', $unpaidStatuses);
            })
                ->get()
                ->filter(fn (Invoice $invoice): bool => $this->dueDate($invoice)->daysUntil($today) === -$daysAfter);

            foreach ($overdueInvoices as $invoice) {
                $this->sendOverdueReminder($invoice, $today);
                $count['overdue']++;
            }

            $this->info("Notifications sent: {$count['upcoming']} upcoming, {$count['due_today']} today, {$count['overdue']} overdue.");

        } catch (\Exception $e) {
            $this->error('Error while checking invoices: ' . $e->getMessage());
            Log::error('Error while checking invoices: ' . $e->getMessage(), ['exception' => $e]);

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * Send reminder for upcoming due invoice
     */
    private function sendUpcomingReminder(Invoice $invoice, DateTimeImmutable $today): void
    {
        // Get the invoice with its relations
        $invoice->load(['supplier', 'client']);

        $this->info("Sending a reminder about the upcoming due date for invoice #{$invoice->invoice_vs}");

        // Send notification to supplier if we have their email
        if ($invoice->supplier && $invoice->supplier->email) {
            $locale = $invoice->supplier->preferredLocale();
            $this->info("- Using language for : {$locale}");
            $this->notifyInvoice(
                InvoiceReminderType::UPCOMING_DUE,
                $invoice,
                recipientType: 'supplier',
                locale: $locale,
                daysLeft: $this->dueDate($invoice)->daysUntil($today),
            );
        }

        // Send notification to client if we have their email
        if ($invoice->client && $invoice->client->email) {
            $locale = $invoice->client->preferredLocale();
            $this->info("- Using language for client: {$locale}");
            $this->notifyInvoice(
                InvoiceReminderType::UPCOMING_DUE,
                $invoice,
                recipientType: 'client',
                locale: $locale,
                daysLeft: $this->dueDate($invoice)->daysUntil($today),
            );
        }
    }

    /**
     * Send reminder for invoice due today
     */
    private function sendDueTodayReminder(Invoice $invoice): void
    {
        // Get the invoice with its relations
        $invoice->load(['supplier', 'client']);

        $this->info("Sending a reminder about today's due date for invoice #{$invoice->invoice_vs}");

        // Send notification to supplier if we have their email
        if ($invoice->supplier && $invoice->supplier->email) {
            $locale = $invoice->supplier->preferredLocale();
            $this->info("- Using language for supplier: {$locale}");
            $this->notifyInvoice(
                InvoiceReminderType::DUE_TODAY,
                $invoice,
                recipientType: 'supplier',
                locale: $locale,
            );
        }

        // Send notification to client if we have their email
        if ($invoice->client && $invoice->client->email) {
            $locale = $invoice->client->preferredLocale();
            $this->info("- Using language for client: {$locale}");
            $this->notifyInvoice(
                InvoiceReminderType::DUE_TODAY,
                $invoice,
                recipientType: 'client',
                locale: $locale,
            );
        }
    }

    /**
     * Send reminder for overdue invoice
     */
    private function sendOverdueReminder(Invoice $invoice, DateTimeImmutable $today): void
    {
        // Get the invoice with its relations
        $invoice->load(['supplier', 'client']);

        $this->info("Sending a reminder about overdue invoice #{$invoice->invoice_vs}");

        // Send notification to supplier if we have their email
        if ($invoice->supplier && $invoice->supplier->email) {
            $locale = $invoice->supplier->preferredLocale();
            $this->info("- Using language for supplier: {$locale}");
            $this->notifyInvoice(
                InvoiceReminderType::OVERDUE,
                $invoice,
                recipientType: 'supplier',
                locale: $locale,
                daysOverdue: -$this->dueDate($invoice)->daysUntil($today),
            );
        }

        // Send notification to client if we have their email
        if ($invoice->client && $invoice->client->email) {
            $locale = $invoice->client->preferredLocale();
            $this->info("- Using language for client: {$locale}");
            $this->notifyInvoice(
                InvoiceReminderType::OVERDUE,
                $invoice,
                recipientType: 'client',
                locale: $locale,
                daysOverdue: -$this->dueDate($invoice)->daysUntil($today),
            );
        }
    }

    private function dueDate(Invoice $invoice): InvoiceDueDate
    {
        return InvoiceDueDate::fromIssueDateAndDays($invoice->issue_date->toDateTimeImmutable(), $invoice->due_in);
    }

    /**
     * Build reminder payload and dispatch via notifier
     */
    private function notifyInvoice(
        InvoiceReminderType $type,
        Invoice $invoice,
        string $recipientType,
        ?string $locale = null,
        ?int $daysLeft = null,
        ?int $daysOverdue = null,
    ): void {
        /** @var InvoiceReminderNotifierInterface $notifier */
        $notifier = app(InvoiceReminderNotifierInterface::class);
        $payload = new InvoiceReminderPayload(
            invoiceNumber: $invoice->invoice_vs,
            dueDate: $this->dueDate($invoice)->date(),
            daysLeft: $daysLeft,
            daysOverdue: $daysOverdue,
            recipientType: $recipientType,
            locale: $locale,
        );
        $recipient = new Recipient(
            name: $recipientType === 'supplier' ? ($invoice->supplier->name ?? '') : ($invoice->client->name ?? ''),
            email: $recipientType === 'supplier' ? ($invoice->supplier->email ?? '') : ($invoice->client->email ?? ''),
            preferredLocale: $locale,
        );
        $notifier->send(
            $type,
            $payload,
            $recipient,
        );
    }
}
