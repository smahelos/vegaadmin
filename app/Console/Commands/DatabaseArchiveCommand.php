<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DatabaseArchiveCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:archive {--table=invoices} {--dry-run} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Archive old records based on retention policies';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tableName = $this->option('table');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info("Starting database archiving for table: {$tableName}");

        // Get archive policy for the table
        $policy = DB::table('archive_policies')
            ->where('table_name', $tableName)
            ->where('enabled', true)
            ->first();

        if (!$policy) {
            $this->error("No archive policy found for table: {$tableName}");
            return Command::FAILURE;
        }

        $cutoffDate = Carbon::now()->subMonths($policy->retention_months);
        $this->info("Archiving records older than: {$cutoffDate->format('Y-m-d')}");

        // Check if we have records to archive
        $recordsToArchive = DB::table($tableName)
            ->where($policy->date_column, '<', $cutoffDate)
            ->count();

        if ($recordsToArchive === 0) {
            $this->info("No records found for archiving.");
            return Command::SUCCESS;
        }

        $this->info("Found {$recordsToArchive} records to archive.");

        if ($dryRun) {
            $this->warn("DRY RUN: No actual archiving performed.");
            return Command::SUCCESS;
        }

        if (!$force && $this->input->isInteractive() && !$this->confirm("Do you want to proceed with archiving {$recordsToArchive} records?")) {
            $this->info("Archiving cancelled.");
            return Command::SUCCESS;
        }

        // Log maintenance task
        $maintenanceId = DB::table('database_maintenance_log')->insertGetId([
            'task_type' => 'archive',
            'table_name' => $tableName,
            'status' => 'running',
            'description' => "Archiving records older than {$cutoffDate->format('Y-m-d')}",
            'started_at' => now(),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        try {
            if ($tableName === 'invoices') {
                $this->archiveInvoices($cutoffDate, $policy);
            } else {
                $this->genericArchive($tableName, $cutoffDate, $policy);
            }

            // Update maintenance log
            DB::table('database_maintenance_log')
                ->where('id', $maintenanceId)
                ->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'results' => json_encode(['records_archived' => $recordsToArchive]),
                    'updated_at' => now()
                ]);

            // Update archive policy
            DB::table('archive_policies')
                ->where('table_name', $tableName)
                ->update([
                    'last_archived_at' => now(),
                    'records_archived' => DB::raw('records_archived + ' . $recordsToArchive),
                    'updated_at' => now()
                ]);

            $this->info("Successfully archived {$recordsToArchive} records.");
            return Command::SUCCESS;

        } catch (\Exception $e) {
            // Update maintenance log with error
            DB::table('database_maintenance_log')
                ->where('id', $maintenanceId)
                ->update([
                    'status' => 'failed',
                    'completed_at' => now(),
                    'results' => json_encode(['error' => $e->getMessage()]),
                    'updated_at' => now()
                ]);

            $this->error("Archiving failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Archive invoices with related products
     */
    private function archiveInvoices($cutoffDate, $policy)
    {
        $this->info("Archiving invoices with related products...");

        // Get invoices to archive
        $invoicesToArchive = DB::table('invoices')
            ->where($policy->date_column, '<', $cutoffDate)
            ->get();

        DB::beginTransaction();

        foreach ($invoicesToArchive as $invoice) {
            // Get related products
            $products = DB::table('invoice_products')
                ->where('invoice_id', $invoice->id)
                ->get();

            // Archive invoice
            $archiveInvoiceId = DB::table('invoices_archive')->insertGetId([
                'original_id' => $invoice->id,
                'user_id' => $invoice->user_id,
                'client_id' => $invoice->client_id,
                'invoice_vs' => $invoice->invoice_vs,
                'invoice_ks' => $invoice->invoice_ks,
                'invoice_ss' => $invoice->invoice_ss,
                'issue_date' => $invoice->issue_date,
                'due_in' => $invoice->due_in,
                'payment_amount' => $invoice->payment_amount ?? 0,
                'payment_currency' => $invoice->payment_currency ?? 'EUR',
                'payment_method_id' => $invoice->payment_method_id,
                'payment_status_id' => $invoice->payment_status_id,
                'invoice_data' => json_encode($invoice),
                'archived_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Archive products if any
            if ($products->count() > 0) {
                DB::table('invoice_products_archive')->insert([
                    'original_invoice_id' => $invoice->id,
                    'archive_invoice_id' => $archiveInvoiceId,
                    'products_data' => json_encode($products->toArray()),
                    'archived_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                // Delete original products
                DB::table('invoice_products')->where('invoice_id', $invoice->id)->delete();
            }

            // Delete original invoice
            DB::table('invoices')->where('id', $invoice->id)->delete();
        }

        DB::commit();
    }

    /**
     * Generic archive for other tables
     */
    private function genericArchive($tableName, $cutoffDate, $policy)
    {
        $this->info("Performing generic archiving for table: {$tableName}");
        
        // Simple deletion for non-critical tables
        DB::table($tableName)
            ->where($policy->date_column, '<', $cutoffDate)
            ->delete();
    }
}
