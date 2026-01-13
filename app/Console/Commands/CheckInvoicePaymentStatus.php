<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\StatusCategory;
use App\Domain\Invoice\Notifications\Contracts\InvoiceReminderNotifierInterface;
use App\Domain\Invoice\Notifications\DTO\InvoiceReminderPayload;
use App\Domain\Invoice\Notifications\Enums\InvoiceReminderType;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Infrastructure\Shared\Locale\Traits\HasPreferredLocale;

class CheckInvoicePaymentStatus extends Command
{
    use HasPreferredLocale;

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
    public function handle()
    {
        $daysBefore = (int) $this->option('days-before');
        $daysAfter = (int) $this->option('days-after');
        $today = Carbon::today();
        $count = ['upcoming' => 0, 'due_today' => 0, 'overdue' => 0];

        // Get statuses from database and exclude 'paid' and 'cancelled'
        $paidStatusSlugs = ['paid', 'cancelled'];
        $unpaidStatuses = \App\Models\Status::whereNotIn('slug', $paidStatusSlugs)
            ->where('category_id', StatusCategory::where('slug', 'invoice-payment')->first()->id ?? null)
            ->pluck('slug')
            ->toArray();

        $this->info('Checking invoice for payment reminder...');

        try {
            // 1. Invoices that are due in the next X days
            // Get all unpaid invoices and filter by due date in PHP (database-agnostic)
            $upcomingDueInvoices = Invoice::whereHas('paymentStatus', function ($query) use ($unpaidStatuses) {
                    $query->whereIn('slug', $unpaidStatuses);
                })
                ->get()
                ->filter(function ($invoice) use ($today, $daysBefore) {
                    $dueDate = Carbon::parse($invoice->issue_date)->addDays($invoice->due_in);
                    return $dueDate->equalTo($today->copy()->addDays($daysBefore));
                });

            foreach ($upcomingDueInvoices as $invoice) {
                $this->sendUpcomingReminder($invoice);
                $count['upcoming']++;
            }

            // 2. invoices that are due today
            $dueTodayInvoices = Invoice::whereHas('paymentStatus', function ($query) use ($unpaidStatuses) {
                    $query->whereIn('slug', $unpaidStatuses);
                })
                ->get()
                ->filter(function ($invoice) use ($today) {
                    $dueDate = Carbon::parse($invoice->issue_date)->addDays($invoice->due_in);
                    return $dueDate->equalTo($today);
                });

            foreach ($dueTodayInvoices as $invoice) {
                $this->sendDueTodayReminder($invoice);
                $count['due_today']++;
            }

            // 3. Invoices that are overdue (e.g. 1 day after due date)
            $overdueInvoices = Invoice::whereHas('paymentStatus', function ($query) use ($unpaidStatuses) {
                    $query->whereIn('slug', $unpaidStatuses);
                })
                ->get()
                ->filter(function ($invoice) use ($today, $daysAfter) {
                    $dueDate = Carbon::parse($invoice->issue_date)->addDays($invoice->due_in);
                    return $dueDate->equalTo($today->copy()->subDays($daysAfter));
                });

            foreach ($overdueInvoices as $invoice) {
                $this->sendOverdueReminder($invoice);
                $count['overdue']++;
            }

            $this->info("Notifications sent: {$count['upcoming']} upcoming, {$count['due_today']} today, {$count['overdue']} overdue.");

        } catch (\Exception $e) {
            $this->error("Error while checking invoices: " . $e->getMessage());
            Log::error("Error while checking invoices: " . $e->getMessage(), ['exception' => $e]);
        }

        return Command::SUCCESS;
    }

    /**
     * Send reminder for upcoming due invoice
     */
    private function sendUpcomingReminder(Invoice $invoice)
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
                daysLeft: now()->diffInDays(\Carbon\Carbon::parse($invoice->issue_date)->addDays($invoice->due_in), false)
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
                daysLeft: now()->diffInDays(\Carbon\Carbon::parse($invoice->issue_date)->addDays($invoice->due_in), false)
            );
        }
    }

    /**
     * Send reminder for invoice due today
     */
    private function sendDueTodayReminder(Invoice $invoice)
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
                locale: $locale
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
                locale: $locale
            );
        }
    }

    /**
     * Send reminder for overdue invoice
     */
    private function sendOverdueReminder(Invoice $invoice)
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
                daysOverdue: \Carbon\Carbon::parse($invoice->issue_date)->addDays($invoice->due_in)->diffInDays(now())
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
                daysOverdue: \Carbon\Carbon::parse($invoice->issue_date)->addDays($invoice->due_in)->diffInDays(now())
            );
        }

        // Actually change the status of the invoice to 'overdue'
        if ($invoice->payment_status_slug !== 'overdue') {
            $overdueStatus = \App\Models\Status::where('slug', 'overdue')->first();
            if ($overdueStatus) {
                $invoice->payment_status_id = $overdueStatus->id;
                $invoice->save();
                $this->info("Invoice status #{$invoice->invoice_vs} changed to 'overdue'");
            }
        }
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
            dueDate: new \DateTimeImmutable(\Carbon\Carbon::parse($invoice->issue_date)->addDays($invoice->due_in)->format('Y-m-d')),
            daysLeft: $daysLeft,
            daysOverdue: $daysOverdue,
            recipientType: $recipientType,
            locale: $locale,
        );
        $recipient = new \App\Domain\Shared\Notifications\DTO\Recipient(
            name: $recipientType === 'supplier' ? ($invoice->supplier->name ?? '') : ($invoice->client->name ?? ''),
            email: $recipientType === 'supplier' ? ($invoice->supplier->email ?? '') : ($invoice->client->email ?? ''),
            preferredLocale: $locale,
        );
        $notifier->send($type, $payload, $recipient);
    }
}
