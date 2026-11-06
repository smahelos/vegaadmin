<?php

namespace Tests\Feature\Models;

use App\Domain\Shared\Console\Enums\ScheduledCommand;
use App\Models\CronTask;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Feature tests for CronTask Model
 *
 * Tests database operations, business logic, and model behavior requiring database interactions
 * Tests cron task creation, running and cron expression generation
 */
class CronTaskFeatureTest extends TestCase
{
    use WithFaker;

    #[Test]
    public function can_create_cron_task_with_factory(): void
    {
        $cronTaskFactory = CronTask::factory();
        $cronTask = $cronTaskFactory->create();

        $this->assertDatabaseHas(
            'cron_tasks',
            [
                'id' => $cronTask->id,
                'name' => $cronTask->name,
                'command' => $cronTask->command,
                'frequency' => $cronTask->frequency,
            ],
        );
    }

    #[Test]
    public function casts_work_correctly(): void
    {
        $cronTaskFactory = CronTask::factory();
        $cronTask = $cronTaskFactory->create(
            [
                'run_at' => '15:30:00',
                'last_run' => '2025-06-17 10:00:00',
                'is_active' => 1,
                'day_of_week' => '3',
                'day_of_month' => '15',
            ],
        );

        // run_at uses datetime cast
        $this->assertInstanceOf(Carbon::class, $cronTask->run_at);
        $this->assertEquals(
            '15:30:00',
            $cronTask->run_at->format('H:i:s'),
        );

        // last_run uses datetime cast
        $this->assertInstanceOf(Carbon::class, $cronTask->last_run);
        $this->assertIsBool($cronTask->is_active);
        $this->assertTrue($cronTask->is_active);
        $this->assertIsInt($cronTask->day_of_week);
        $this->assertEquals(3, $cronTask->day_of_week);
        $this->assertIsInt($cronTask->day_of_month);
        $this->assertEquals(15, $cronTask->day_of_month);
    }

    #[Test]
    public function factory_states_work_correctly(): void
    {
        $cronTaskFactory = CronTask::factory();
        $activeFactory = $cronTaskFactory->active();
        $activeTask = $activeFactory->create();
        $inactiveFactory = $cronTaskFactory->inactive();
        $inactiveTask = $inactiveFactory->create();
        $dailyFactory = $cronTaskFactory->daily();
        $dailyTask = $dailyFactory->create();
        $weeklyFactory = $cronTaskFactory->weekly();
        $weeklyTask = $weeklyFactory->create();
        $monthlyFactory = $cronTaskFactory->monthly();
        $monthlyTask = $monthlyFactory->create();
        $customFactory = $cronTaskFactory->custom();
        $customTask = $customFactory->create();

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
        $cronTaskFactory = CronTask::factory();
        $recentlyRunFactory = $cronTaskFactory->recentlyRun();
        $recentTask = $recentlyRunFactory->create();

        $this->assertNotNull($recentTask->last_run);
        $now = CarbonImmutable::now();
        $this->assertTrue(
            $recentTask->last_run->isAfter($now->subDay()),
        );
        $this->assertNotNull($recentTask->last_output);
        $this->assertStringContainsString('successfully', $recentTask->last_output);
    }

    #[Test]
    public function factory_with_error_state_works(): void
    {
        $cronTaskFactory = CronTask::factory();
        $failingFactory = $cronTaskFactory->withError();
        $errorTask = $failingFactory->create();

        $this->assertNotNull($errorTask->last_run);
        $this->assertNotNull($errorTask->last_output);
        $this->assertStringContainsString('ERROR:', $errorTask->last_output);
    }

    #[Test]
    public function get_cron_expression_works_for_daily_task(): void
    {
        $cronTaskFactory = CronTask::factory();
        $dailyFactory = $cronTaskFactory->daily();
        $dailyTask = $dailyFactory->create(['run_at' => '08:30:00']);

        $cronExpression = $dailyTask->getCronExpression();

        $this->assertIsString($cronExpression);
        $this->assertStringContainsString('30 08 * * *', $cronExpression);
    }

    #[Test]
    public function get_cron_expression_works_for_weekly_task(): void
    {
        $cronTaskFactory = CronTask::factory();
        $weeklyFactory = $cronTaskFactory->weekly();
        $weeklyTask = $weeklyFactory->create([
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
        $cronTaskFactory = CronTask::factory();
        $monthlyFactory = $cronTaskFactory->monthly();
        $monthlyTask = $monthlyFactory->create([
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
        $cronTaskFactory = CronTask::factory();
        $customFactory = $cronTaskFactory->custom();
        $customTask = $customFactory->create([
            'custom_expression' => '0 */6 * * *', // Every 6 hours
        ]);

        $cronExpression = $customTask->getCronExpression();

        $this->assertEquals('0 */6 * * *', $cronExpression);
    }

    #[Test]
    public function get_next_run_date_returns_carbon_instance_or_null(): void
    {
        $cronTaskFactory = CronTask::factory();
        $dailyFactory = $cronTaskFactory->daily();
        $dailyTask = $dailyFactory->create();
        $customFactory = $cronTaskFactory->custom();
        $customTask = $customFactory->create();

        $nextRunDate1 = $dailyTask->getNextRunDate();
        $nextRunDate2 = $customTask->getNextRunDate();

        $this->assertTrue($nextRunDate1 instanceof Carbon || $nextRunDate1 === null);
        $this->assertTrue($nextRunDate2 instanceof Carbon || $nextRunDate2 === null);
    }

    #[Test]
    public function simulate_run_runs_the_command_with_its_options_and_stores_the_run(): void
    {
        Mail::fake();
        Notification::fake();
        Queue::fake();
        $cronTaskFactory = CronTask::factory();
        $commandFactory = $cronTaskFactory->withCommand(
            ScheduledCommand::CHECK_INVOICE_PAYMENT_STATUS,
            '--days-before=5 --days-after=2',
        );
        $task = $commandFactory->create(['last_run' => null]);

        $succeeded = $task->simulateRun();

        $this->assertTrue($succeeded);
        $task->refresh();
        $this->assertNotNull($task->last_run);
        $this->assertStringContainsString('Notifications sent', $task->last_output);
    }

    #[Test]
    public function simulate_run_reports_a_command_that_rejects_its_options_as_failed(): void
    {
        $cronTaskFactory = CronTask::factory();
        $commandFactory = $cronTaskFactory->withCommand(
            ScheduledCommand::CHECK_INVOICE_PAYMENT_STATUS,
            '--days-before=soon',
        );
        $task = $commandFactory->create();

        $this->assertFalse(
            $task->simulateRun(),
        );
    }

    #[Test]
    public function simulate_run_reports_an_unknown_option_as_failed_with_the_error(): void
    {
        $cronTaskFactory = CronTask::factory();
        $commandFactory = $cronTaskFactory->withCommand(
            ScheduledCommand::CHECK_INVOICE_PAYMENT_STATUS,
            '--no-such-option',
        );
        $task = $commandFactory->create();

        $succeeded = $task->simulateRun();

        $this->assertFalse($succeeded);
        $task->refresh();
        $this->assertStringStartsWith(
            __(
                'cron_tasks.messages.run_error',
                ['error' => ''],
            ),
            $task->last_output,
        );
    }

    #[Test]
    public function can_update_cron_task(): void
    {
        $cronTaskFactory = CronTask::factory();
        $cronTask = $cronTaskFactory->create();

        $newData = [
            'name' => 'Updated Task Name',
            'is_active' => false,
            'description' => 'Updated description',
        ];

        $cronTask->update($newData);

        $this->assertDatabaseHas(
            'cron_tasks',
            array_merge(
                ['id' => $cronTask->id],
                $newData,
            ),
        );
    }

    #[Test]
    public function can_delete_cron_task(): void
    {
        $cronTaskFactory = CronTask::factory();
        $cronTask = $cronTaskFactory->create();
        $cronTaskId = $cronTask->id;

        $cronTask->delete();

        $this->assertDatabaseMissing('cron_tasks', ['id' => $cronTaskId]);
    }

    #[Test]
    public function can_create_cron_task_without_optional_fields(): void
    {
        $data = [
            'name' => 'Simple Task',
            'command' => ScheduledCommand::OPTIMIZE_DATABASE,
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
}
