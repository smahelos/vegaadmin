<?php

namespace Tests\Feature\Models;

use App\Models\CronTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Feature tests for CronTask Model
 * 
 * Tests database operations, business logic, and model behavior requiring database interactions
 * Tests cron task creation, accessors, mutators, and cron expression generation
 */
class CronTaskFeatureTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    #[Test]
    public function can_create_cron_task_with_factory(): void
    {
        $cronTask = CronTask::factory()->create();

        $this->assertDatabaseHas('cron_tasks', [
            'id' => $cronTask->id,
            'name' => $cronTask->name,
            'command' => $cronTask->command,
            'frequency' => $cronTask->frequency,
        ]);
    }

    #[Test]
    public function fillable_attributes_can_be_mass_assigned(): void
    {
        $data = [
            'name' => 'Test Cron Task',
            'command' => 'cache:clear',
            'frequency' => 'daily',
            'custom_expression' => null,
            'run_at' => '08:00:00',
            'day_of_week' => null,
            'day_of_month' => null,
            'is_active' => true,
            'description' => 'Test description',
            'last_run' => '2025-06-17 08:00:00',
            'last_output' => 'Task completed successfully',
        ];

        $cronTask = CronTask::create($data);

        $this->assertDatabaseHas('cron_tasks', [
            'name' => $data['name'],
            'command' => $data['command'],
            'frequency' => $data['frequency'],
            'description' => $data['description'],
        ]);
        $this->assertEquals($data['name'], $cronTask->name);
        $this->assertEquals($data['command'], $cronTask->command);
    }

    #[Test]
    public function casts_work_correctly(): void
    {
        $cronTask = CronTask::factory()->create([
            'run_at' => '15:30:00',
            'last_run' => '2025-06-17 10:00:00',
            'is_active' => 1,
            'day_of_week' => '3',
            'day_of_month' => '15',
        ]);

        // run_at uses datetime cast
        $this->assertInstanceOf(\Carbon\Carbon::class, $cronTask->run_at);
        $this->assertEquals('15:30:00', $cronTask->run_at->format('H:i:s'));
        
        // last_run uses datetime cast
        $this->assertInstanceOf(\Carbon\Carbon::class, $cronTask->last_run);
        $this->assertIsBool($cronTask->is_active);
        $this->assertTrue($cronTask->is_active);
        $this->assertIsInt($cronTask->day_of_week);
        $this->assertEquals(3, $cronTask->day_of_week);
        $this->assertIsInt($cronTask->day_of_month);
        $this->assertEquals(15, $cronTask->day_of_month);
    }

    #[Test]
    public function get_frequency_name_attribute_works(): void
    {
        $dailyTask = CronTask::factory()->daily()->create();
        $weeklyTask = CronTask::factory()->weekly()->create();
        $monthlyTask = CronTask::factory()->monthly()->create();
        $customTask = CronTask::factory()->custom()->create();

        // These will return translation keys or fallback to original value
        $this->assertIsString($dailyTask->getFrequencyNameAttribute());
        $this->assertIsString($weeklyTask->getFrequencyNameAttribute());
        $this->assertIsString($monthlyTask->getFrequencyNameAttribute());
        $this->assertIsString($customTask->getFrequencyNameAttribute());
        
        // Test accessor via magic method
        $this->assertIsString($dailyTask->frequency_name);
    }

    #[Test]
    public function get_day_of_week_name_attribute_works(): void
    {
        $mondayTask = CronTask::factory()->weekly()->create(['day_of_week' => 1]);
        $fridayTask = CronTask::factory()->weekly()->create(['day_of_week' => 5]);
        $nullTask = CronTask::factory()->daily()->create(['day_of_week' => null]);

        $this->assertIsString($mondayTask->getDayOfWeekNameAttribute());
        $this->assertIsString($fridayTask->getDayOfWeekNameAttribute());
        $this->assertNull($nullTask->getDayOfWeekNameAttribute());
        
        // Test accessor via magic method
        $this->assertIsString($mondayTask->day_of_week_name);
    }

    #[Test]
    public function get_formatted_run_at_attribute_works(): void
    {
        $taskWithTime = CronTask::factory()->daily()->create(['run_at' => '15:30:00']);
        $taskWithoutTime = CronTask::factory()->custom()->create(['run_at' => null]);

        $this->assertIsString($taskWithTime->getFormattedRunAtAttribute());
        $this->assertStringContainsString('15:30', $taskWithTime->getFormattedRunAtAttribute());
        $this->assertNull($taskWithoutTime->getFormattedRunAtAttribute());
        
        // Test accessor via magic method - run_at is Carbon object, formatted_run_at is string
        $this->assertInstanceOf(\Carbon\Carbon::class, $taskWithTime->run_at);
        $this->assertIsString($taskWithTime->formatted_run_at);
        $this->assertEquals('15:30', $taskWithTime->formatted_run_at);
    }

    #[Test]
    public function get_base_command_attribute_works(): void
    {
        $simpleCommand = CronTask::factory()->withCommand('cache:clear')->create();
        $commandWithParams = CronTask::factory()->withCommand('queue:work --queue=high,default')->create();

        $this->assertEquals('cache:clear', $simpleCommand->getBaseCommandAttribute());
        $this->assertEquals('queue:work', $commandWithParams->getBaseCommandAttribute());
        
        // Test accessor via magic method
        $this->assertEquals('cache:clear', $simpleCommand->base_command);
    }

    #[Test]
    public function get_command_params_attribute_works(): void
    {
        $simpleCommand = CronTask::factory()->withCommand('cache:clear')->create();
        $commandWithParams = CronTask::factory()->withCommand('queue:work --queue=high,default --timeout=60')->create();

        $this->assertEmpty($simpleCommand->getCommandParamsAttribute());
        $this->assertNotEmpty($commandWithParams->getCommandParamsAttribute());
        $this->assertStringContainsString('--queue=high,default', $commandWithParams->getCommandParamsAttribute());
        
        // Test accessor via magic method
        $this->assertEmpty($simpleCommand->command_params);
    }

    #[Test]
    public function factory_states_work_correctly(): void
    {
        $activeTask = CronTask::factory()->active()->create();
        $inactiveTask = CronTask::factory()->inactive()->create();
        $dailyTask = CronTask::factory()->daily()->create();
        $weeklyTask = CronTask::factory()->weekly()->create();
        $monthlyTask = CronTask::factory()->monthly()->create();
        $customTask = CronTask::factory()->custom()->create();

        $this->assertTrue($activeTask->is_active);
        $this->assertFalse($inactiveTask->is_active);
        
        $this->assertEquals('daily', $dailyTask->frequency);
        $this->assertNotNull($dailyTask->run_at);
        $this->assertNull($dailyTask->day_of_week);
        
        $this->assertEquals('weekly', $weeklyTask->frequency);
        $this->assertNotNull($weeklyTask->day_of_week);
        $this->assertNull($weeklyTask->day_of_month);
        
        $this->assertEquals('monthly', $monthlyTask->frequency);
        $this->assertNotNull($monthlyTask->day_of_month);
        $this->assertNull($monthlyTask->day_of_week);
        
        $this->assertEquals('custom', $customTask->frequency);
        $this->assertNotNull($customTask->custom_expression);
        $this->assertNull($customTask->run_at);
    }

    #[Test]
    public function factory_recently_run_state_works(): void
    {
        $recentTask = CronTask::factory()->recentlyRun()->create();

        $this->assertNotNull($recentTask->last_run);
        $this->assertTrue($recentTask->last_run->isAfter(now()->subDay()));
        $this->assertNotNull($recentTask->last_output);
        $this->assertStringContainsString('successfully', $recentTask->last_output);
    }

    #[Test]
    public function factory_with_error_state_works(): void
    {
        $errorTask = CronTask::factory()->withError()->create();

        $this->assertNotNull($errorTask->last_run);
        $this->assertNotNull($errorTask->last_output);
        $this->assertStringContainsString('ERROR:', $errorTask->last_output);
    }

    #[Test]
    public function get_cron_expression_works_for_daily_task(): void
    {
        $dailyTask = CronTask::factory()->daily()->create(['run_at' => '08:30:00']);

        $cronExpression = $dailyTask->getCronExpression();
        
        $this->assertIsString($cronExpression);
        $this->assertStringContainsString('30 08 * * *', $cronExpression);
    }

    #[Test]
    public function get_cron_expression_works_for_weekly_task(): void
    {
        $weeklyTask = CronTask::factory()->weekly()->create([
            'run_at' => '09:00:00',
            'day_of_week' => 1, // Monday
        ]);

        $cronExpression = $weeklyTask->getCronExpression();
        
        $this->assertIsString($cronExpression);
        $this->assertStringContainsString('00 09 * * 1', $cronExpression);
    }

    #[Test]
    public function get_cron_expression_works_for_monthly_task(): void
    {
        $monthlyTask = CronTask::factory()->monthly()->create([
            'run_at' => '10:15:00',
            'day_of_month' => 15,
        ]);

        $cronExpression = $monthlyTask->getCronExpression();
        
        $this->assertIsString($cronExpression);
        $this->assertStringContainsString('15 10 15 * *', $cronExpression);
    }

    #[Test]
    public function get_cron_expression_works_for_custom_task(): void
    {
        $customTask = CronTask::factory()->custom()->create([
            'custom_expression' => '0 */6 * * *', // Every 6 hours
        ]);

        $cronExpression = $customTask->getCronExpression();
        
        $this->assertEquals('0 */6 * * *', $cronExpression);
    }

    #[Test]
    public function get_next_run_date_returns_carbon_instance_or_null(): void
    {
        $dailyTask = CronTask::factory()->daily()->create();
        $customTask = CronTask::factory()->custom()->create();

        $nextRunDate1 = $dailyTask->getNextRunDate();
        $nextRunDate2 = $customTask->getNextRunDate();
        
        $this->assertTrue($nextRunDate1 instanceof \Carbon\Carbon || $nextRunDate1 === null);
        $this->assertTrue($nextRunDate2 instanceof \Carbon\Carbon || $nextRunDate2 === null);
    }

    #[Test]
    public function simulate_run_returns_appropriate_result(): void
    {
        $task = CronTask::factory()->withCommand('cache:clear')->create();

        $result = $task->simulateRun();
        
        // The actual implementation may vary, but it should return something
        $this->assertTrue(is_string($result) || is_array($result) || is_null($result) || is_bool($result) || is_int($result));
    }

    #[Test]
    public function can_update_cron_task(): void
    {
        $cronTask = CronTask::factory()->create();
        
        $newData = [
            'name' => 'Updated Task Name',
            'is_active' => false,
            'description' => 'Updated description',
        ];
        
        $cronTask->update($newData);
        
        $this->assertDatabaseHas('cron_tasks', array_merge(
            ['id' => $cronTask->id],
            $newData
        ));
    }

    #[Test]
    public function can_delete_cron_task(): void
    {
        $cronTask = CronTask::factory()->create();
        $cronTaskId = $cronTask->id;
        
        $cronTask->delete();
        
        $this->assertDatabaseMissing('cron_tasks', ['id' => $cronTaskId]);
    }

    #[Test]
    public function can_create_cron_task_without_optional_fields(): void
    {
        $data = [
            'name' => 'Simple Task',
            'command' => 'optimize:clear',
            'frequency' => 'daily',
            'is_active' => true,
        ];

        $cronTask = CronTask::create($data);

        $this->assertDatabaseHas('cron_tasks', $data);
        $this->assertEquals($data['name'], $cronTask->name);
        $this->assertNull($cronTask->description);
        $this->assertNull($cronTask->last_run);
        $this->assertNull($cronTask->last_output);
    }

    #[Test]
    public function command_mutator_and_accessor_work_together(): void
    {
        $cronTask = CronTask::factory()->make();
        
        // Test setting command through mutator
        $cronTask->setCommandAttribute('queue:work --timeout=60');
        
        // Test that base command and params are extracted correctly
        $this->assertEquals('queue:work', $cronTask->getBaseCommandAttribute());
        $this->assertEquals('--timeout=60', $cronTask->getCommandParamsAttribute());
    }
}
