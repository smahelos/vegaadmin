<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PerformanceMetric;

class CleanPerformanceMetrics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:clean-metrics 
                            {--days=90 : Number of days to keep}
                            {--type=old : Type of cleanup: old, sample, duplicate, all}
                            {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean old performance metrics data to prevent database bloat';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $days = $this->option('days');
        $type = $this->option('type');
        $dryRun = $this->option('dry-run');
        
        $this->info("Starting performance metrics cleanup...");
        $this->info("Type: {$type}, Days to keep: {$days}, Dry run: " . ($dryRun ? 'Yes' : 'No'));
        
        try {
            $deletedCount = 0;
            
            switch ($type) {
                case 'old':
                    $query = PerformanceMetric::where('measured_at', '<', now()->subDays($days));
                    break;
                    
                case 'sample':
                    $query = PerformanceMetric::whereJsonContains('metadata->generated', true);
                    break;
                    
                case 'duplicate':
                    return $this->cleanDuplicates($dryRun);
                    
                case 'all':
                    if (!$this->confirm('Are you sure you want to delete ALL performance metrics?')) {
                        $this->warn('Operation cancelled.');
                        return 1;
                    }
                    $query = PerformanceMetric::query();
                    break;
                    
                default:
                    $this->error("Invalid cleanup type: {$type}");
                    return 1;
            }
            
            if ($dryRun) {
                $count = $query->count();
                $this->info("Would delete {$count} metrics");
                
                // Show sample of what would be deleted
                $sample = $query->limit(5)->get(['id', 'metric_type', 'measured_at']);
                if ($sample->count() > 0) {
                    $this->table(['ID', 'Type', 'Measured At'], $sample->toArray());
                }
            } else {
                $deletedCount = $query->delete();
                $this->info("Deleted {$deletedCount} performance metrics");
            }
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("Cleanup failed: " . $e->getMessage());
            
            return 1;
        }
    }
    
    /**
     * Clean duplicate metrics
     */
    private function cleanDuplicates($dryRun = false)
    {
        $this->info("Cleaning duplicate metrics...");
        
        // Get duplicates grouped by type and date
        $duplicates = PerformanceMetric::selectRaw('metric_type, DATE(measured_at) as date, COUNT(*) as count')
            ->groupBy('metric_type', \DB::raw('DATE(measured_at)'))
            ->havingRaw('COUNT(*) > 1')
            ->get();
            
        if ($duplicates->isEmpty()) {
            $this->info("No duplicates found.");
            return 0;
        }
        
        $this->info("Found {$duplicates->count()} groups with duplicates");
        
        $totalDeleted = 0;
        
        foreach ($duplicates as $duplicate) {
            // Get all metrics for this type/date
            $metrics = PerformanceMetric::where('metric_type', $duplicate->metric_type)
                ->whereDate('measured_at', $duplicate->date)
                ->orderBy('measured_at', 'desc')
                ->get();
                
            // Keep the latest one, delete the rest
            $toDelete = $metrics->skip(1);
            
            if ($dryRun) {
                $this->line("Would delete {$toDelete->count()} duplicates for {$duplicate->metric_type} on {$duplicate->date}");
            } else {
                $deletedIds = $toDelete->pluck('id');
                PerformanceMetric::whereIn('id', $deletedIds)->delete();
                $totalDeleted += $toDelete->count();
            }
        }
        
        if (!$dryRun) {
            $this->info("Deleted {$totalDeleted} duplicate metrics");
        }
        
        return 0;
    }
}
