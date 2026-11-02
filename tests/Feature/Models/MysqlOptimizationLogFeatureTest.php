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
        $log = MysqlOptimizationLog::factory()->highPriority()->create();

        $this->assertEquals('high', $log->priority);
    }

    #[Test]
    public function factory_medium_priority_state_creates_medium_priority_log(): void
    {
        $log = MysqlOptimizationLog::factory()->mediumPriority()->create();

        $this->assertEquals('medium', $log->priority);
    }

    #[Test]
    public function factory_low_priority_state_creates_low_priority_log(): void
    {
        $log = MysqlOptimizationLog::factory()->lowPriority()->create();

        $this->assertEquals('low', $log->priority);
    }

    #[Test]
    public function factory_applied_state_creates_applied_log(): void
    {
        $log = MysqlOptimizationLog::factory()->applied()->create();

        $this->assertTrue($log->applied);
    }

    #[Test]
    public function factory_unapplied_state_creates_unapplied_log(): void
    {
        $log = MysqlOptimizationLog::factory()->unapplied()->create();

        $this->assertFalse($log->applied);
    }

    #[Test]
    public function factory_buffer_pool_size_state_creates_specific_optimization(): void
    {
        $log = MysqlOptimizationLog::factory()->bufferPoolSize()->create();

        $this->assertEquals('innodb_buffer_pool_size', $log->setting_name);
        $this->assertEquals('128M', $log->current_value);
        $this->assertEquals('1G', $log->recommended_value);
        $this->assertEquals('high', $log->priority);
    }

    #[Test]
    public function factory_max_connections_state_creates_specific_optimization(): void
    {
        $log = MysqlOptimizationLog::factory()->maxConnections()->create();

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

        $mysqlOptimizationLogFactory2 = MysqlOptimizationLog::factory();
        $log = $mysqlOptimizationLogFactory2->create(['applied' => '0']);
        $this->assertIsBool($log->fresh()->applied);
        $this->assertFalse($log->fresh()->applied);
    }

    #[Test]
    public function can_query_logs_by_priority(): void
    {
        MysqlOptimizationLog::factory()->highPriority()->create();
        MysqlOptimizationLog::factory()->mediumPriority()->create();
        MysqlOptimizationLog::factory()->lowPriority()->createMany(2);

        $mysqlOptimizationLogQuery = MysqlOptimizationLog::where('priority', 'high');
        $highPriorityLogs = $mysqlOptimizationLogQuery->get();
        $mysqlOptimizationLogQuery2 = MysqlOptimizationLog::where('priority', 'medium');
        $mediumPriorityLogs = $mysqlOptimizationLogQuery2->get();
        $mysqlOptimizationLogQuery3 = MysqlOptimizationLog::where('priority', 'low');
        $lowPriorityLogs = $mysqlOptimizationLogQuery3->get();

        $this->assertCount(1, $highPriorityLogs);
        $this->assertCount(1, $mediumPriorityLogs);
        $this->assertCount(2, $lowPriorityLogs);
    }

    #[Test]
    public function can_query_logs_by_applied_status(): void
    {
        MysqlOptimizationLog::factory()->applied()->createMany(2);
        MysqlOptimizationLog::factory()->unapplied()->createMany(3);

        $mysqlOptimizationLogQuery = MysqlOptimizationLog::where('applied', true);
        $appliedLogs = $mysqlOptimizationLogQuery->get();
        $mysqlOptimizationLogQuery2 = MysqlOptimizationLog::where('applied', false);
        $unappliedLogs = $mysqlOptimizationLogQuery2->get();

        $this->assertCount(2, $appliedLogs);
        $this->assertCount(3, $unappliedLogs);
    }

    #[Test]
    public function can_query_logs_by_setting_name(): void
    {
        $mysqlOptimizationLogFactory = MysqlOptimizationLog::factory();
        $mysqlOptimizationLogFactory->create(['setting_name' => 'innodb_buffer_pool_size']);
        $mysqlOptimizationLogFactory2 = MysqlOptimizationLog::factory();
        $mysqlOptimizationLogFactory2->create(['setting_name' => 'max_connections']);
        $mysqlOptimizationLogFactory3 = MysqlOptimizationLog::factory();
        $mysqlOptimizationLogFactory3->create(['setting_name' => 'innodb_buffer_pool_size']);

        $mysqlOptimizationLogQuery = MysqlOptimizationLog::where('setting_name', 'innodb_buffer_pool_size');
        $bufferPoolLogs = $mysqlOptimizationLogQuery->get();
        $mysqlOptimizationLogQuery2 = MysqlOptimizationLog::where('setting_name', 'max_connections');
        $maxConnectionsLogs = $mysqlOptimizationLogQuery2->get();

        $this->assertCount(2, $bufferPoolLogs);
        $this->assertCount(1, $maxConnectionsLogs);
    }

    #[Test]
    public function can_update_mysql_optimization_log(): void
    {
        $log = MysqlOptimizationLog::factory()->unapplied()->create();

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
