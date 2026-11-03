<?php

namespace Tests\Feature\Models;

use App\Models\MysqlOptimizationLog;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MysqlOptimizationLogFeatureTest extends TestCase
{
    #[Test]
    public function can_create_mysql_optimization_log(): void
    {
        $data = [
            'setting_name' => 'innodb_buffer_pool_size',
            'current_value' => '128M',
            'recommended_value' => '1G',
            'description' => 'Buffer pool size should be increased',
            'priority' => 'high',
            'applied' => false,
        ];

        $log = MysqlOptimizationLog::create($data);

        $this->assertInstanceOf(MysqlOptimizationLog::class, $log);
        $this->assertDatabaseHas(
            'mysql_optimization_logs',
            [
                'setting_name' => 'innodb_buffer_pool_size',
                'current_value' => '128M',
                'recommended_value' => '1G',
                'priority' => 'high',
            ],
        );
    }

    #[Test]
    public function can_use_factory_to_create_mysql_optimization_log(): void
    {
        $mysqlOptimizationLogFactory = MysqlOptimizationLog::factory();
        $log = $mysqlOptimizationLogFactory->create();

        $this->assertInstanceOf(MysqlOptimizationLog::class, $log);
        $this->assertDatabaseHas('mysql_optimization_logs', [
            'id' => $log->id,
        ]);
    }

    #[Test]
    public function factory_high_priority_state_creates_high_priority_log(): void
    {
        $mysqlOptimizationLogFactory = MysqlOptimizationLog::factory();
        $highPriorityFactory = $mysqlOptimizationLogFactory->highPriority();
        $log = $highPriorityFactory->create();

        $this->assertEquals('high', $log->priority);
    }

    #[Test]
    public function factory_medium_priority_state_creates_medium_priority_log(): void
    {
        $mysqlOptimizationLogFactory = MysqlOptimizationLog::factory();
        $mediumPriorityFactory = $mysqlOptimizationLogFactory->mediumPriority();
        $log = $mediumPriorityFactory->create();

        $this->assertEquals('medium', $log->priority);
    }

    #[Test]
    public function factory_low_priority_state_creates_low_priority_log(): void
    {
        $mysqlOptimizationLogFactory = MysqlOptimizationLog::factory();
        $lowPriorityFactory = $mysqlOptimizationLogFactory->lowPriority();
        $log = $lowPriorityFactory->create();

        $this->assertEquals('low', $log->priority);
    }

    #[Test]
    public function factory_applied_state_creates_applied_log(): void
    {
        $mysqlOptimizationLogFactory = MysqlOptimizationLog::factory();
        $appliedFactory = $mysqlOptimizationLogFactory->applied();
        $log = $appliedFactory->create();

        $this->assertTrue($log->applied);
    }

    #[Test]
    public function factory_unapplied_state_creates_unapplied_log(): void
    {
        $mysqlOptimizationLogFactory = MysqlOptimizationLog::factory();
        $unappliedFactory = $mysqlOptimizationLogFactory->unapplied();
        $log = $unappliedFactory->create();

        $this->assertFalse($log->applied);
    }

    #[Test]
    public function factory_buffer_pool_size_state_creates_specific_optimization(): void
    {
        $mysqlOptimizationLogFactory = MysqlOptimizationLog::factory();
        $bufferPoolSizeFactory = $mysqlOptimizationLogFactory->bufferPoolSize();
        $log = $bufferPoolSizeFactory->create();

        $this->assertEquals('innodb_buffer_pool_size', $log->setting_name);
        $this->assertEquals('128M', $log->current_value);
        $this->assertEquals('1G', $log->recommended_value);
        $this->assertEquals('high', $log->priority);
    }

    #[Test]
    public function factory_max_connections_state_creates_specific_optimization(): void
    {
        $mysqlOptimizationLogFactory = MysqlOptimizationLog::factory();
        $maxConnectionsFactory = $mysqlOptimizationLogFactory->maxConnections();
        $log = $maxConnectionsFactory->create();

        $this->assertEquals('max_connections', $log->setting_name);
        $this->assertEquals('151', $log->current_value);
        $this->assertEquals('300', $log->recommended_value);
        $this->assertEquals('medium', $log->priority);
    }

    #[Test]
    public function applied_attribute_is_cast_to_boolean(): void
    {
        $mysqlOptimizationLogFactory = MysqlOptimizationLog::factory();
        $log = $mysqlOptimizationLogFactory->create(['applied' => '1']);

        $this->assertIsBool($log->applied);
        $this->assertTrue($log->applied);

        $log = $mysqlOptimizationLogFactory->create(['applied' => '0']);
        $this->assertIsBool($log->fresh()->applied);
        $this->assertFalse($log->fresh()->applied);
    }

    #[Test]
    public function can_query_logs_by_priority(): void
    {
        $mysqlOptimizationLogFactory = MysqlOptimizationLog::factory();
        $highPriorityFactory = $mysqlOptimizationLogFactory->highPriority();
        $highPriorityFactory->create();
        $mediumPriorityFactory = $mysqlOptimizationLogFactory->mediumPriority();
        $mediumPriorityFactory->create();
        $lowPriorityFactory = $mysqlOptimizationLogFactory->lowPriority();
        $lowPriorityFactory->createMany(2);

        $mysqlOptimizationLogQuery = MysqlOptimizationLog::where('priority', 'high');
        $highPriorityLogs = $mysqlOptimizationLogQuery->get();
        $mysqlOptimizationLogQuery = MysqlOptimizationLog::where('priority', 'medium');
        $mediumPriorityLogs = $mysqlOptimizationLogQuery->get();
        $mysqlOptimizationLogQuery = MysqlOptimizationLog::where('priority', 'low');
        $lowPriorityLogs = $mysqlOptimizationLogQuery->get();

        $this->assertCount(1, $highPriorityLogs);
        $this->assertCount(1, $mediumPriorityLogs);
        $this->assertCount(2, $lowPriorityLogs);
    }

    #[Test]
    public function can_query_logs_by_applied_status(): void
    {
        $mysqlOptimizationLogFactory = MysqlOptimizationLog::factory();
        $appliedFactory = $mysqlOptimizationLogFactory->applied();
        $appliedFactory->createMany(2);
        $unappliedFactory = $mysqlOptimizationLogFactory->unapplied();
        $unappliedFactory->createMany(3);

        $mysqlOptimizationLogQuery = MysqlOptimizationLog::where('applied', true);
        $appliedLogs = $mysqlOptimizationLogQuery->get();
        $mysqlOptimizationLogQuery = MysqlOptimizationLog::where('applied', false);
        $unappliedLogs = $mysqlOptimizationLogQuery->get();

        $this->assertCount(2, $appliedLogs);
        $this->assertCount(3, $unappliedLogs);
    }

    #[Test]
    public function can_query_logs_by_setting_name(): void
    {
        $mysqlOptimizationLogFactory = MysqlOptimizationLog::factory();
        $mysqlOptimizationLogFactory->create(['setting_name' => 'innodb_buffer_pool_size']);
        $mysqlOptimizationLogFactory->create(['setting_name' => 'max_connections']);
        $mysqlOptimizationLogFactory->create(['setting_name' => 'innodb_buffer_pool_size']);

        $mysqlOptimizationLogQuery = MysqlOptimizationLog::where('setting_name', 'innodb_buffer_pool_size');
        $bufferPoolLogs = $mysqlOptimizationLogQuery->get();
        $mysqlOptimizationLogQuery = MysqlOptimizationLog::where('setting_name', 'max_connections');
        $maxConnectionsLogs = $mysqlOptimizationLogQuery->get();

        $this->assertCount(2, $bufferPoolLogs);
        $this->assertCount(1, $maxConnectionsLogs);
    }

    #[Test]
    public function can_update_mysql_optimization_log(): void
    {
        $mysqlOptimizationLogFactory = MysqlOptimizationLog::factory();
        $unappliedFactory = $mysqlOptimizationLogFactory->unapplied();
        $log = $unappliedFactory->create();

        $log->update([
            'applied' => true,
            'current_value' => '1G',
        ]);

        $this->assertTrue($log->fresh()->applied);
        $this->assertEquals('1G', $log->fresh()->current_value);
    }

    #[Test]
    public function can_delete_mysql_optimization_log(): void
    {
        $mysqlOptimizationLogFactory = MysqlOptimizationLog::factory();
        $log = $mysqlOptimizationLogFactory->create();
        $logId = $log->id;

        $log->delete();

        $this->assertDatabaseMissing('mysql_optimization_logs', [
            'id' => $logId,
        ]);
    }
}
