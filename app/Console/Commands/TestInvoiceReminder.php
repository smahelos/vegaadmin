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
    protected $signature = 'invoices:test-reminder {invoice_id : ID faktury pro test} {type=all : Typ připomenutí (upcoming, due, overdue, all)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test odeslání připomenutí pro vybranou fakturu';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $invoiceId = $this->argument('invoice_id');
        $type = $this->argument('type');
        
        $invoice = Invoice::find($invoiceId);
        if (!$invoice) {
            $this->error("Faktura s ID {$invoiceId} nebyla nalezena");
            return Command::FAILURE;
        }

        $this->info("Testování připomenutí pro fakturu #{$invoice->invoice_vs}");

        if ($type === 'upcoming' || $type === 'all') {
            $this->testUpcomingReminder($invoice);
        }
        
        if ($type === 'due' || $type === 'all') {
            $this->testDueReminder($invoice);
        }
        
        if ($type === 'overdue' || $type === 'all') {
            $this->testOverdueReminder($invoice);
        }

        $this->info('Testování dokončeno');
        return Command::SUCCESS;
    }

    private function testUpcomingReminder($invoice)
    {
        $this->info('Testování připomenutí o blížící se splatnosti...');
        
        if ($invoice->supplier && $invoice->supplier->email) {
            $invoice->supplier->notify(new InvoiceUpcomingDueReminder($invoice));
            $this->info("Odesláno dodavateli: {$invoice->supplier->email}");
        } else {
            $this->warn('Dodavatel nemá email pro odeslání');
        }
        
        if ($invoice->client && $invoice->client->email) {
            $invoice->client->notify(new InvoiceUpcomingDueReminder($invoice, 'client'));
            $this->info("Odesláno klientovi: {$invoice->client->email}");
        } else {
            $this->warn('Klient nemá email pro odeslání');
        }
    }

    private function testDueReminder($invoice)
    {
        $this->info('Testování připomenutí o dnešní splatnosti...');
        
        if ($invoice->supplier && $invoice->supplier->email) {
            $invoice->supplier->notify(new InvoiceDueReminder($invoice));
            $this->info("Odesláno dodavateli: {$invoice->supplier->email}");
        } else {
            $this->warn('Dodavatel nemá email pro odeslání');
        }
        
        if ($invoice->client && $invoice->client->email) {
            $invoice->client->notify(new InvoiceDueReminder($invoice, 'client'));
            $this->info("Odesláno klientovi: {$invoice->client->email}");
        } else {
            $this->warn('Klient nemá email pro odeslání');
        }
    }

    private function testOverdueReminder($invoice)
    {
        $this->info('Testování připomenutí o překročení splatnosti...');
        
        if ($invoice->supplier && $invoice->supplier->email) {
            $invoice->supplier->notify(new InvoiceOverdueReminder($invoice));
            $this->info("Odesláno dodavateli: {$invoice->supplier->email}");
        } else {
            $this->warn('Dodavatel nemá email pro odeslání');
        }
        
        if ($invoice->client && $invoice->client->email) {
            $invoice->client->notify(new InvoiceOverdueReminder($invoice, 'client'));
            $this->info("Odesláno klientovi: {$invoice->client->email}");
        } else {
            $this->warn('Klient nemá email pro odeslání');
        }
    }
}
