<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use stdClass;

final class DatabaseArchiveCommand extends Command
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
    public function handle(): int
    {
        $tableName = $this->option('table');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info("Starting database archiving for table: {$tableName}");

        // Get archive policy for the table
        $query = DB::table('archive_policies');
        $query->where('table_name', $tableName);
        $query->where('enabled', true);
        $policy = $query->first();

        if (!$policy) {
            $this->error("No archive policy found for table: {$tableName}");

            return Command::FAILURE;
        }

        $cutoffDate = CarbonImmutable::now();
        $cutoffDate = $cutoffDate->subMonths($policy->retention_months);
        $this->info(
            "Archiving records older than: {$cutoffDate->format('Y-m-d')}",
        );

        // Check if we have records to archive
        $query = DB::table($tableName);
        $query->where(
            $policy->date_column,
            '<',
            $cutoffDate,
        );
        $recordsToArchive = $query->count();

        if ($recordsToArchive === 0) {
            $this->info('No records found for archiving.');

            return Command::SUCCESS;
        }

        $this->info("Found {$recordsToArchive} records to archive.");

        if ($dryRun) {
            $this->warn('DRY RUN: No actual archiving performed.');

            return Command::SUCCESS;
        }

        if (
            !$force
            && $this->input->isInteractive()
            && !$this->confirm(
                "Do you want to proceed with archiving {$recordsToArchive} records?",
            )
        ) {
            $this->info('Archiving cancelled.');

            return Command::SUCCESS;
        }

        // Log maintenance task
        $query = DB::table('database_maintenance_logs');
        $maintenanceId = $query->insertGetId(
            [
                'task_type' => 'archive',
                'table_name' => $tableName,
                'status' => 'running',
                'description' => "Archiving records older than {$cutoffDate->format('Y-m-d')}",
                'started_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        try {
            if ($tableName === 'invoices') {
                $this->archiveInvoices($cutoffDate, $policy);
            } else {
                $this->genericArchive(
                    $tableName,
                    $cutoffDate,
                    $policy,
                );
            }

            // Update maintenance log
            $query = DB::table('database_maintenance_logs');
            $query->where('id', $maintenanceId);
            $query->update(
                [
                    'status' => 'completed',
                    'completed_at' => now(),
                    'results' => json_encode(['records_archived' => $recordsToArchive]),
                    'updated_at' => now(),
                ],
            );

            // Update archive policy
            $query = DB::table('archive_policies');
            $query->where('table_name', $tableName);
            $query->update(
                [
                    'last_archived_at' => now(),
                    'records_archived' => DB::raw('records_archived + ' . $recordsToArchive),
                    'updated_at' => now(),
                ],
            );

            $this->info("Successfully archived {$recordsToArchive} records.");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            // Update maintenance log with error
            $query = DB::table('database_maintenance_logs');
            $query->where('id', $maintenanceId);
            $query->update(
                [
                    'status' => 'failed',
                    'completed_at' => now(),
                    'results' => json_encode(['error' => $e->getMessage()]),
                    'updated_at' => now(),
                ],
            );

            $this->error('Archiving failed: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Archive invoices with related products
     */
    private function archiveInvoices(CarbonImmutable $cutoffDate, stdClass $policy): void
    {
        $this->info('Archiving invoices with related products...');

        // Get invoices to archive
        $query = DB::table('invoices');
        $query->where(
            $policy->date_column,
            '<',
            $cutoffDate,
        );
        $invoicesToArchive = $query->get();

        DB::beginTransaction();

        foreach ($invoicesToArchive as $invoice) {
            // Get related products
            $query = DB::table('invoice_products');
            $query->where('invoice_id', $invoice->id);
            $products = $query->get();

            // Archive invoice
            $query = DB::table('invoices_archive');
            $archiveInvoiceId = $query->insertGetId(
                [
                    'original_id' => $invoice->id,
                    'user_id' => $invoice->user_id,
                    'business_id' => $invoice->business_id,
                    'contact_id' => $invoice->contact_id,
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
                    'updated_at' => now(),
                ],
            );

            // Archive products if any
            if ($products->count() > 0) {
                $query = DB::table('invoice_products_archive');
                $query->insert(
                    [
                        'original_invoice_id' => $invoice->id,
                        'archive_invoice_id' => $archiveInvoiceId,
                        'products_data' => json_encode($products->toArray()),
                        'archived_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                );

                // Delete original products
                $query = DB::table('invoice_products');
                $query->where('invoice_id', $invoice->id);
                $query->delete();
            }

            // Delete original invoice
            $query = DB::table('invoices');
            $query->where('id', $invoice->id);
            $query->delete();
        }

        DB::commit();
    }

    /**
     * Generic archive for other tables
     */
    private function genericArchive(
        string $tableName,
        CarbonImmutable $cutoffDate,
        stdClass $policy,
    ): void {
        $this->info("Performing generic archiving for table: {$tableName}");

        // Simple deletion for non-critical tables
        $query = DB::table($tableName);
        $query->where(
            $policy->date_column,
            '<',
            $cutoffDate,
        );
        $query->delete();
    }
}
