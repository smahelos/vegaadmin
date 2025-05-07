<?php

namespace App\Console\Commands;

use App\Models\Invoice;
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

        // Možné stavy faktur na základě tvé codebase (z _Data/Dockers/Production/vegaadmin/lang/en/invoices.php)
        // Chceme kontrolovat jen faktury, které nejsou zaplacené nebo zrušené
        // (předpokládám, že 'paid' a 'cancelled' jsou stavy, které nebudeme kontrolovat)
        // TODO: načítat stavy z DB, pokud je to možné
        $unpaidStatuses = ['pending', 'overdue', 'partially_paid'];

        $this->info('Kontrola faktur pro připomenutí plateb...');

        try {
            // 1. Faktury, které budou brzy splatné (např. za 3 dny)
            $upcomingDueInvoices = Invoice::whereHas('paymentStatus', function ($query) use ($unpaidStatuses) {
                    $query->whereIn('slug', $unpaidStatuses);
                })
                ->whereRaw('DATE_ADD(issue_date, INTERVAL due_in DAY) = ?', [$today->copy()->addDays($daysBefore)])
                ->get();

            foreach ($upcomingDueInvoices as $invoice) {
                $this->sendUpcomingReminder($invoice);
                $count['upcoming']++;
            }

            // 2. Faktury, které jsou splatné dnes
            $dueTodayInvoices = Invoice::whereHas('paymentStatus', function ($query) use ($unpaidStatuses) {
                    $query->whereIn('slug', $unpaidStatuses);
                })
                ->whereRaw('DATE_ADD(issue_date, INTERVAL due_in DAY) = ?', [$today])
                ->get();

            foreach ($dueTodayInvoices as $invoice) {
                $this->sendDueTodayReminder($invoice);
                $count['due_today']++;
            }

            // 3. Faktury, které jsou po splatnosti (např. 1 den po)
            $overdueInvoices = Invoice::whereHas('paymentStatus', function ($query) use ($unpaidStatuses) {
                    $query->whereIn('slug', $unpaidStatuses);
                })
                ->whereRaw('DATE_ADD(issue_date, INTERVAL due_in DAY) = ?', [$today->copy()->subDays($daysAfter)])
                ->get();

            foreach ($overdueInvoices as $invoice) {
                $this->sendOverdueReminder($invoice);
                $count['overdue']++;
            }

            $this->info("Odesláno upozornění: {$count['upcoming']} nastávajících, {$count['due_today']} dnešních, {$count['overdue']} po splatnosti.");

        } catch (\Exception $e) {
            $this->error("Chyba při kontrole faktur: " . $e->getMessage());
            Log::error("Chyba při kontrole faktur: " . $e->getMessage(), ['exception' => $e]);
        }

        return Command::SUCCESS;
    }

    /**
     * Odeslat připomenutí o nadcházející splatnosti faktury
     */
    private function sendUpcomingReminder(Invoice $invoice)
    {
        // Načtení vztahů pro správné zobrazení v emailu
        $invoice->load(['supplier', 'client']);
        
        $this->info("Odesílám připomenutí o blížící se splatnosti pro fakturu #{$invoice->invoice_vs}");
        
        // Odeslat notifikaci dodavateli, pokud máme jeho email
        if ($invoice->supplier && $invoice->supplier->email) {
            // Detekce jazyka dodavatele
            $locale = $invoice->supplier->preferredLocale();
            $this->info("- Používám jazyk pro dodavatele: {$locale}");
            
            $invoice->supplier->notify(new InvoiceUpcomingDueReminder($invoice));
        }
        
        // Odeslat notifikaci klientovi, pokud máme jeho email
        if ($invoice->client && $invoice->client->email) {
            // Detekce jazyka klienta
            $locale = $invoice->client->preferredLocale();
            $this->info("- Používám jazyk pro klienta: {$locale}");
            
            $invoice->client->notify(new InvoiceUpcomingDueReminder($invoice, 'client'));
        }
    }

    /**
     * Odeslat připomenutí o faktuře splatné dnes
     */
    private function sendDueTodayReminder(Invoice $invoice)
    {
        // Načtení vztahů pro správné zobrazení v emailu
        $invoice->load(['supplier', 'client']);
        
        $this->info("Odesílám připomenutí o splatnosti dnes pro fakturu #{$invoice->invoice_vs}");
        
        // Odeslat notifikaci dodavateli, pokud máme jeho email
        if ($invoice->supplier && $invoice->supplier->email) {
            // Detekce jazyka dodavatele
            $locale = $invoice->supplier->preferredLocale();
            $this->info("- Používám jazyk pro dodavatele: {$locale}");

            $invoice->supplier->notify(new InvoiceDueReminder($invoice));
        }
        
        // Odeslat notifikaci klientovi, pokud máme jeho email
        if ($invoice->client && $invoice->client->email) {
            // Detekce jazyka klienta
            $locale = $invoice->client->preferredLocale();
            $this->info("- Používám jazyk pro klienta: {$locale}");

            $invoice->client->notify(new InvoiceDueReminder($invoice, 'client'));
        }
    }

    /**
     * Odeslat připomenutí o faktuře po splatnosti
     */
    private function sendOverdueReminder(Invoice $invoice)
    {
        // Načtení vztahů pro správné zobrazení v emailu
        $invoice->load(['supplier', 'client']);
        
        $this->info("Odesílám připomenutí o překročení splatnosti pro fakturu #{$invoice->invoice_vs}");
        
        // Odeslat notifikaci dodavateli, pokud máme jeho email
        if ($invoice->supplier && $invoice->supplier->email) {
            // Detekce jazyka dodavatele
            $locale = $invoice->supplier->preferredLocale();
            $this->info("- Používám jazyk pro dodavatele: {$locale}");

            $invoice->supplier->notify(new InvoiceOverdueReminder($invoice));
        }
        
        // Odeslat notifikaci klientovi, pokud máme jeho email
        if ($invoice->client && $invoice->client->email) {
            // Detekce jazyka klienta
            $locale = $invoice->client->preferredLocale();
            $this->info("- Používám jazyk pro klienta: {$locale}");

            $invoice->client->notify(new InvoiceOverdueReminder($invoice, 'client'));
        }

        // Aktualizovat status faktury na "overdue", pokud ještě není
        if ($invoice->payment_status_slug !== 'overdue') {
            $overdueStatus = \App\Models\Status::where('slug', 'overdue')->first();
            if ($overdueStatus) {
                $invoice->payment_status_id = $overdueStatus->id;
                $invoice->save();
                $this->info("Status faktury #{$invoice->invoice_vs} změněn na 'overdue'");
            }
        }
    }
}
