<?php

namespace Tests\Feature\Models;

use App\Models\DatabaseMaintenanceLog;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DatabaseMaintenanceLogFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function can_create_database_maintenance_log(): void
    {
        $data = [
            'task_type' => 'optimize',
            'table_name' => 'users',
            'status' => 'pending',
            'description' => 'Optimizing users table',
            'results' => ['rows_processed' => 1000],
            'started_at' => now(),
            'completed_at' => now()->addMinutes(5)
        ];

        $log = DatabaseMaintenanceLog::create($data);

        $this->assertInstanceOf(DatabaseMaintenanceLog::class, $log);
        $this->assertDatabaseHas('database_maintenance_logs', [
            'task_type' => 'optimize',
            'table_name' => 'users',
            'status' => 'pending',
            'description' => 'Optimizing users table'
        ]);
    }

    #[Test]
    public function can_use_factory_to_create_database_maintenance_log(): void
    {
        $log = DatabaseMaintenanceLog::factory()->create();

        $this->assertInstanceOf(DatabaseMaintenanceLog::class, $log);
        $this->assertDatabaseHas('database_maintenance_logs', [
            'id' => $log->id
        ]);
    }

    #[Test]
    public function factory_pending_state_creates_pending_log(): void
    {
        $log = DatabaseMaintenanceLog::factory()->pending()->create();

        $this->assertEquals('pending', $log->status);
        $this->assertNull($log->started_at);
        $this->assertNull($log->completed_at);
    }

    #[Test]
    public function factory_running_state_creates_running_log(): void
    {
        $log = DatabaseMaintenanceLog::factory()->running()->create();

        $this->assertEquals('running', $log->status);
        $this->assertNotNull($log->started_at);
        $this->assertNull($log->completed_at);
    }

    #[Test]
    public function factory_completed_state_creates_completed_log(): void
    {
        $log = DatabaseMaintenanceLog::factory()->completed()->create();

        $this->assertEquals('completed', $log->status);
        $this->assertNotNull($log->started_at);
        $this->assertNotNull($log->completed_at);
        $this->assertTrue($log->completed_at >= $log->started_at);
    }

    #[Test]
    public function factory_failed_state_creates_failed_log(): void
    {
        $log = DatabaseMaintenanceLog::factory()->failed()->create();

        $this->assertEquals('failed', $log->status);
        $this->assertNotNull($log->started_at);
        $this->assertNotNull($log->completed_at);
        $this->assertArrayHasKey('error_message', $log->results);
        $this->assertArrayHasKey('error_code', $log->results);
    }

    #[Test]
    public function results_attribute_is_cast_to_array(): void
    {
        $log = DatabaseMaintenanceLog::factory()->create([
            'results' => ['test' => 'value']
        ]);

        $this->assertIsArray($log->results);
        $this->assertEquals(['test' => 'value'], $log->results);
    }

    #[Test]
    public function started_at_attribute_is_cast_to_datetime(): void
    {
        $log = DatabaseMaintenanceLog::factory()->create([
            'started_at' => '2023-01-01 10:00:00'
        ]);

        $this->assertInstanceOf(Carbon::class, $log->started_at);
        $this->assertEquals('2023-01-01 10:00:00', $log->started_at->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function completed_at_attribute_is_cast_to_datetime(): void
    {
        $log = DatabaseMaintenanceLog::factory()->create([
            'completed_at' => '2023-01-01 11:00:00'
        ]);

        $this->assertInstanceOf(Carbon::class, $log->completed_at);
        $this->assertEquals('2023-01-01 11:00:00', $log->completed_at->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function duration_attribute_calculates_correctly(): void
    {
        $startedAt = Carbon::parse('2023-01-01 10:00:00');
        $completedAt = Carbon::parse('2023-01-01 10:05:00');

        $log = DatabaseMaintenanceLog::factory()->create([
            'started_at' => $startedAt,
            'completed_at' => $completedAt
        ]);

        $duration = $log->duration;
        $this->assertNotNull($duration);
        $this->assertIsString($duration);
    }

    #[Test]
    public function can_query_logs_by_status(): void
    {
        DatabaseMaintenanceLog::factory()->pending()->create();
        DatabaseMaintenanceLog::factory()->running()->create();
        DatabaseMaintenanceLog::factory()->completed()->createMany(2);

        $pendingLogs = DatabaseMaintenanceLog::where('status', 'pending')->get();
        $runningLogs = DatabaseMaintenanceLog::where('status', 'running')->get();
        $completedLogs = DatabaseMaintenanceLog::where('status', 'completed')->get();

        $this->assertCount(1, $pendingLogs);
        $this->assertCount(1, $runningLogs);
        $this->assertCount(2, $completedLogs);
    }

    #[Test]
    public function can_query_logs_by_task_type(): void
    {
        DatabaseMaintenanceLog::factory()->create(['task_type' => 'optimize']);
        DatabaseMaintenanceLog::factory()->create(['task_type' => 'analyze']);
        DatabaseMaintenanceLog::factory()->create(['task_type' => 'optimize']);

        $optimizeLogs = DatabaseMaintenanceLog::where('task_type', 'optimize')->get();
        $analyzeLogs = DatabaseMaintenanceLog::where('task_type', 'analyze')->get();

        $this->assertCount(2, $optimizeLogs);
        $this->assertCount(1, $analyzeLogs);
    }

    #[Test]
    public function can_query_logs_by_table_name(): void
    {
        DatabaseMaintenanceLog::factory()->create(['table_name' => 'users']);
        DatabaseMaintenanceLog::factory()->create(['table_name' => 'invoices']);
        DatabaseMaintenanceLog::factory()->create(['table_name' => 'users']);

        $userLogs = DatabaseMaintenanceLog::where('table_name', 'users')->get();
        $invoiceLogs = DatabaseMaintenanceLog::where('table_name', 'invoices')->get();

        $this->assertCount(2, $userLogs);
        $this->assertCount(1, $invoiceLogs);
    }

    #[Test]
    public function can_update_database_maintenance_log(): void
    {
        $log = DatabaseMaintenanceLog::factory()->pending()->create();

        $log->update([
            'status' => 'running',
            'started_at' => now()
        ]);

        $this->assertEquals('running', $log->fresh()->status);
        $this->assertNotNull($log->fresh()->started_at);
    }

    #[Test]
    public function can_delete_database_maintenance_log(): void
    {
        $log = DatabaseMaintenanceLog::factory()->create();
        $logId = $log->id;

        $log->delete();

        $this->assertDatabaseMissing('database_maintenance_logs', [
            'id' => $logId
        ]);
    }
}
