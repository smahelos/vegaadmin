<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Notifications\InvoiceDueReminder;
use App\Notifications\InvoiceOverdueReminder;
use App\Notifications\InvoiceUpcomingDueReminder;
use Illuminate\Console\Command;

class TestInvoiceReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:test-reminder {invoice_id : invoice IF to test} {type=all : Reminder type (upcoming, due, overdue, all)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Invoice reminder test for selected invoice';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $invoiceId = $this->argument('invoice_id');
        $type = $this->argument('type');
        
        $invoice = Invoice::find($invoiceId);
        if (!$invoice) {
            $this->error("Unvoice with ID {$invoiceId} not found");
            return Command::FAILURE;
        }

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
        return Command::SUCCESS;
    }

    private function testUpcomingReminder($invoice)
    {
        $this->info('Invoice reminder test about upcomming due date...');
        
        if ($invoice->supplier && $invoice->supplier->email) {
            $invoice->supplier->notify(new InvoiceUpcomingDueReminder($invoice));
            $this->info("Sent to supplier: {$invoice->supplier->email}");
        } else {
            $this->warn('Supplier has not email to send information to.');
        }
        
        if ($invoice->client && $invoice->client->email) {
            $invoice->client->notify(new InvoiceUpcomingDueReminder($invoice, 'client'));
            $this->info("Sent to client: {$invoice->client->email}");
        } else {
            $this->warn('Client  has not email to send information to.');
        }
    }

    private function testDueReminder($invoice)
    {
        $this->info('Invoice reminder test about due date is today...');
        
        if ($invoice->supplier && $invoice->supplier->email) {
            $invoice->supplier->notify(new InvoiceDueReminder($invoice));
            $this->info("Sent to supplier: {$invoice->supplier->email}");
        } else {
            $this->warn('Supplier has not email to send information to.');
        }
        
        if ($invoice->client && $invoice->client->email) {
            $invoice->client->notify(new InvoiceDueReminder($invoice, 'client'));
            $this->info("Sent to client: {$invoice->client->email}");
        } else {
            $this->warn('Client has not email to send information to.');
        }
    }

    private function testOverdueReminder($invoice)
    {
        $this->info('Invoice reminder test about overdue...');
        
        if ($invoice->supplier && $invoice->supplier->email) {
            $invoice->supplier->notify(new InvoiceOverdueReminder($invoice));
            $this->info("Sent to supplier: {$invoice->supplier->email}");
        } else {
            $this->warn('Supplier has not email to send information to.');
        }
        
        if ($invoice->client && $invoice->client->email) {
            $invoice->client->notify(new InvoiceOverdueReminder($invoice, 'client'));
            $this->info("Sent to client: {$invoice->client->email}");
        } else {
            $this->warn('Client has not email to send information to.');
        }
    }
}
