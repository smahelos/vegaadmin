<?php

namespace Tests\Feature\Models;

use App\Models\DatabaseMaintenanceLog;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DatabaseMaintenanceLogFeatureTest extends TestCase
{
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
            'completed_at' => now()->addMinutes(5),
        ];

        $log = DatabaseMaintenanceLog::create($data);

        $this->assertInstanceOf(DatabaseMaintenanceLog::class, $log);
        $this->assertDatabaseHas(
            'database_maintenance_logs',
            [
                'task_type' => 'optimize',
                'table_name' => 'users',
                'status' => 'pending',
                'description' => 'Optimizing users table',
            ],
        );
    }

    #[Test]
    public function can_use_factory_to_create_database_maintenance_log(): void
    {

        $databaseMaintenanceLogFactory = DatabaseMaintenanceLog::factory();
        $log = $databaseMaintenanceLogFactory->create();

        $this->assertInstanceOf(DatabaseMaintenanceLog::class, $log);
        $this->assertDatabaseHas('database_maintenance_logs', [
            'id' => $log->id,
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

        $databaseMaintenanceLogFactory = DatabaseMaintenanceLog::factory();
        $log = $databaseMaintenanceLogFactory->create([
            'results' => ['test' => 'value'],
        ]);

        $this->assertIsArray($log->results);
        $this->assertEquals(['test' => 'value'], $log->results);
    }

    #[Test]
    public function started_at_attribute_is_cast_to_datetime(): void
    {

        $databaseMaintenanceLogFactory = DatabaseMaintenanceLog::factory();
        $log = $databaseMaintenanceLogFactory->create([
            'started_at' => '2023-01-01 10:00:00',
        ]);

        $this->assertInstanceOf(Carbon::class, $log->started_at);
        $this->assertEquals(
            '2023-01-01 10:00:00',
            $log->started_at->format('Y-m-d H:i:s'),
        );
    }

    #[Test]
    public function completed_at_attribute_is_cast_to_datetime(): void
    {

        $databaseMaintenanceLogFactory = DatabaseMaintenanceLog::factory();
        $log = $databaseMaintenanceLogFactory->create([
            'completed_at' => '2023-01-01 11:00:00',
        ]);

        $this->assertInstanceOf(Carbon::class, $log->completed_at);
        $this->assertEquals(
            '2023-01-01 11:00:00',
            $log->completed_at->format('Y-m-d H:i:s'),
        );
    }

    #[Test]
    public function duration_attribute_calculates_correctly(): void
    {

        $startedAt = Carbon::parse('2023-01-01 10:00:00');
        $completedAt = Carbon::parse('2023-01-01 10:05:00');

        $databaseMaintenanceLogFactory = DatabaseMaintenanceLog::factory();
        $log = $databaseMaintenanceLogFactory->create(
            [
                'started_at' => $startedAt,
                'completed_at' => $completedAt,
            ],
        );

        $duration = $log->duration;
        $this->assertNotNull($duration);
        $this->assertIsString($duration);
    }

    #[Test]
    public function duration_is_empty_until_the_task_has_started_and_completed(): void
    {
        $databaseMaintenanceLogFactory = DatabaseMaintenanceLog::factory();
        $notStarted = $databaseMaintenanceLogFactory->create(['started_at' => null, 'completed_at' => null]);
        $databaseMaintenanceLogFactory2 = DatabaseMaintenanceLog::factory();
        $running = $databaseMaintenanceLogFactory2->create(['started_at' => now(), 'completed_at' => null]);
        $databaseMaintenanceLogFactory3 = DatabaseMaintenanceLog::factory();
        $completedOnly = $databaseMaintenanceLogFactory3->create(['started_at' => null, 'completed_at' => now()]);

        $this->assertNull($notStarted->duration);
        $this->assertNull($running->duration);
        $this->assertNull($completedOnly->duration);
    }

    #[Test]
    public function can_query_logs_by_status(): void
    {

        DatabaseMaintenanceLog::factory()->pending()->create();
        DatabaseMaintenanceLog::factory()->running()->create();
        DatabaseMaintenanceLog::factory()->completed()->createMany(2);

        $databaseMaintenanceLogQuery = DatabaseMaintenanceLog::where('status', 'pending');
        $pendingLogs = $databaseMaintenanceLogQuery->get();
        $databaseMaintenanceLogQuery2 = DatabaseMaintenanceLog::where('status', 'running');
        $runningLogs = $databaseMaintenanceLogQuery2->get();
        $databaseMaintenanceLogQuery3 = DatabaseMaintenanceLog::where('status', 'completed');
        $completedLogs = $databaseMaintenanceLogQuery3->get();

        $this->assertCount(1, $pendingLogs);
        $this->assertCount(1, $runningLogs);
        $this->assertCount(2, $completedLogs);
    }

    #[Test]
    public function can_query_logs_by_task_type(): void
    {

        $databaseMaintenanceLogFactory = DatabaseMaintenanceLog::factory();
        $databaseMaintenanceLogFactory->create(['task_type' => 'optimize']);
        $databaseMaintenanceLogFactory2 = DatabaseMaintenanceLog::factory();
        $databaseMaintenanceLogFactory2->create(['task_type' => 'analyze']);
        $databaseMaintenanceLogFactory3 = DatabaseMaintenanceLog::factory();
        $databaseMaintenanceLogFactory3->create(['task_type' => 'optimize']);

        $databaseMaintenanceLogQuery = DatabaseMaintenanceLog::where('task_type', 'optimize');
        $optimizeLogs = $databaseMaintenanceLogQuery->get();
        $databaseMaintenanceLogQuery2 = DatabaseMaintenanceLog::where('task_type', 'analyze');
        $analyzeLogs = $databaseMaintenanceLogQuery2->get();

        $this->assertCount(2, $optimizeLogs);
        $this->assertCount(1, $analyzeLogs);
    }

    #[Test]
    public function can_query_logs_by_table_name(): void
    {

        $databaseMaintenanceLogFactory = DatabaseMaintenanceLog::factory();
        $databaseMaintenanceLogFactory->create(['table_name' => 'users']);
        $databaseMaintenanceLogFactory2 = DatabaseMaintenanceLog::factory();
        $databaseMaintenanceLogFactory2->create(['table_name' => 'invoices']);
        $databaseMaintenanceLogFactory3 = DatabaseMaintenanceLog::factory();
        $databaseMaintenanceLogFactory3->create(['table_name' => 'users']);

        $databaseMaintenanceLogQuery = DatabaseMaintenanceLog::where('table_name', 'users');
        $userLogs = $databaseMaintenanceLogQuery->get();
        $databaseMaintenanceLogQuery2 = DatabaseMaintenanceLog::where('table_name', 'invoices');
        $invoiceLogs = $databaseMaintenanceLogQuery2->get();

        $this->assertCount(2, $userLogs);
        $this->assertCount(1, $invoiceLogs);
    }

    #[Test]
    public function can_update_database_maintenance_log(): void
    {

        $log = DatabaseMaintenanceLog::factory()->pending()->create();

        $log->update([
            'status' => 'running',
            'started_at' => now(),
        ]);

        $this->assertEquals('running', $log->fresh()->status);
        $this->assertNotNull($log->fresh()->started_at);
    }

    #[Test]
    public function can_delete_database_maintenance_log(): void
    {

        $databaseMaintenanceLogFactory = DatabaseMaintenanceLog::factory();
        $log = $databaseMaintenanceLogFactory->create();
        $logId = $log->id;

        $log->delete();

        $this->assertDatabaseMissing('database_maintenance_logs', [
            'id' => $logId,
        ]);
    }
}
