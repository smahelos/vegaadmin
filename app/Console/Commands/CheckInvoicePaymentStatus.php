<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\StatusCategory;
use App\Notifications\InvoiceDueReminder;
use App\Notifications\InvoiceOverdueReminder;
use App\Notifications\InvoiceUpcomingDueReminder;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Traits\HasPreferredLocale;

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
            $upcomingDueInvoices = Invoice::whereHas('paymentStatus', function ($query) use ($unpaidStatuses) {
                    $query->whereIn('slug', $unpaidStatuses);
                })
                ->whereRaw('DATE_ADD(issue_date, INTERVAL due_in DAY) = ?', [$today->copy()->addDays($daysBefore)])
                ->get();

            foreach ($upcomingDueInvoices as $invoice) {
                $this->sendUpcomingReminder($invoice);
                $count['upcoming']++;
            }

            // 2. invoices that are due today
            $dueTodayInvoices = Invoice::whereHas('paymentStatus', function ($query) use ($unpaidStatuses) {
                    $query->whereIn('slug', $unpaidStatuses);
                })
                ->whereRaw('DATE_ADD(issue_date, INTERVAL due_in DAY) = ?', [$today])
                ->get();

            foreach ($dueTodayInvoices as $invoice) {
                $this->sendDueTodayReminder($invoice);
                $count['due_today']++;
            }

            // 3. Invoices that are overdue (e.g. 1 day after)
            $overdueInvoices = Invoice::whereHas('paymentStatus', function ($query) use ($unpaidStatuses) {
                    $query->whereIn('slug', $unpaidStatuses);
                })
                ->whereRaw('DATE_ADD(issue_date, INTERVAL due_in DAY) = ?', [$today->copy()->subDays($daysAfter)])
                ->get();

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
            // Check if the supplier has a preferred locale
            $locale = $invoice->supplier->preferredLocale();
            $this->info("- Using language for : {$locale}");
            
            $invoice->supplier->notify(new InvoiceUpcomingDueReminder($invoice));
        }
        
        // Send notification to client if we have their email
        if ($invoice->client && $invoice->client->email) {
            // Check if the client has a preferred locale
            $locale = $invoice->client->preferredLocale();
            $this->info("- Using language for client: {$locale}");
            
            $invoice->client->notify(new InvoiceUpcomingDueReminder($invoice, 'client'));
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
            // Check if the supplier has a preferred locale
            $locale = $invoice->supplier->preferredLocale();
            $this->info("- Using language for supplier: {$locale}");

            $invoice->supplier->notify(new InvoiceDueReminder($invoice));
        }
        
        // Send notification to client if we have their email
        if ($invoice->client && $invoice->client->email) {
            // Check if the client has a preferred locale
            $locale = $invoice->client->preferredLocale();
            $this->info("- Using language for client: {$locale}");

            $invoice->client->notify(new InvoiceDueReminder($invoice, 'client'));
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
            // Check if the supplier has a preferred locale
            $locale = $invoice->supplier->preferredLocale();
            $this->info("- Using language for supplier: {$locale}");

            $invoice->supplier->notify(new InvoiceOverdueReminder($invoice));
        }
        
        // Send notification to client if we have their email
        if ($invoice->client && $invoice->client->email) {
            // Check if the client has a preferred locale
            $locale = $invoice->client->preferredLocale();
            $this->info("- Using language for client: {$locale}");

            $invoice->client->notify(new InvoiceOverdueReminder($invoice, 'client'));
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
}
