<?php

namespace Tests\Feature\Console\Commands;

use App\Domain\Shared\Console\Enums\ScheduledCommand;
use App\Models\CronTask;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TestCronExpressionFeatureTest extends TestCase
{
    #[Test]
    public function command_exists_and_has_correct_signature(): void
    {
        $command = $this->pendingCommand('cron:test --help');
        $command->assertExitCode(0);
        $command->run();
    }

    #[Test]
    public function command_fails_without_arguments(): void
    {
        $command = $this->pendingCommand('cron:test');
        $command->assertExitCode(1);
        $command->expectsOutput('You have to set task ID or CRON expression.');
        $command->run();
    }

    #[Test]
    public function command_tests_custom_expression(): void
    {
        $command = $this->pendingCommand('cron:test', ['--expression' => '0 12 * * *']);
        $command->expectsConfirmation('Do yu want to simulate task run? [y/N]', 'no');
        $command->assertExitCode(0);
        $command->expectsOutput('CRON expression testing: 0 12 * * *');
        $command->expectsOutput('Next runs (5):');
        $command->run();
    }

    #[Test]
    public function command_rejects_invalid_expression(): void
    {
        $command = $this->pendingCommand('cron:test', ['--expression' => 'invalid']);
        $command->assertExitCode(1);
        $command->expectsOutputToContain('Incorrect CRON expression:');
        $command->run();
    }

    #[Test]
    public function command_tests_existing_task_with_daily_frequency(): void
    {
        $cronTaskFactory = CronTask::factory();
        $task = $cronTaskFactory->create(
            [
                'name' => 'Test Daily Task',
                'frequency' => 'daily',
                'run_at' => '12:30:00',
                'command' => ScheduledCommand::MONITOR_DATABASE,
            ],
        );

        $command = $this->pendingCommand('cron:test', ['id' => $task->id]);
        $command->expectsConfirmation('Do yu want to simulate task run? [y/N]', 'no');
        $command->assertExitCode(0);
        $command->expectsOutput("Testing of task: {$task->name}");
        $command->expectsOutput('CRON expression: 30 12 * * *');
        $command->expectsOutput('Next runs (5):');
        $command->run();
    }

    #[Test]
    public function command_tests_existing_task_with_weekly_frequency(): void
    {
        $cronTaskFactory = CronTask::factory();
        $task = $cronTaskFactory->create(
            [
                'name' => 'Test Weekly Task',
                'frequency' => 'weekly',
                'run_at' => '09:15:00',
                'day_of_week' => 1, // Monday
                'command' => ScheduledCommand::MONITOR_DATABASE,
            ],
        );

        $command = $this->pendingCommand('cron:test', ['id' => $task->id]);
        $command->expectsConfirmation('Do yu want to simulate task run? [y/N]', 'no');
        $command->assertExitCode(0);
        $command->expectsOutput("Testing of task: {$task->name}");
        $command->expectsOutput('CRON expression: 15 9 * * 1');
        $command->expectsOutput('Next runs (5):');
        $command->run();
    }

    #[Test]
    public function command_tests_existing_task_with_monthly_frequency(): void
    {
        $cronTaskFactory = CronTask::factory();
        $task = $cronTaskFactory->create(
            [
                'name' => 'Test Monthly Task',
                'frequency' => 'monthly',
                'run_at' => '08:00:00',
                'day_of_month' => 15,
                'command' => ScheduledCommand::MONITOR_DATABASE,
            ],
        );

        $command = $this->pendingCommand('cron:test', ['id' => $task->id]);
        $command->expectsConfirmation('Do yu want to simulate task run? [y/N]', 'no');
        $command->assertExitCode(0);
        $command->expectsOutput("Testing of task: {$task->name}");
        $command->expectsOutput('CRON expression: 0 8 15 * *');
        $command->expectsOutput('Next runs (5):');
        $command->run();
    }

    #[Test]
    public function command_tests_existing_task_with_custom_expression(): void
    {
        $cronTaskFactory = CronTask::factory();
        $task = $cronTaskFactory->create(
            [
                'name' => 'Test Custom Task',
                'frequency' => 'custom',
                'custom_expression' => '*/5 * * * *',
                'command' => ScheduledCommand::MONITOR_DATABASE,
            ],
        );

        $command = $this->pendingCommand('cron:test', ['id' => $task->id]);
        $command->expectsConfirmation('Do yu want to simulate task run? [y/N]', 'no');
        $command->assertExitCode(0);
        $command->expectsOutput("Testing of task: {$task->name}");
        $command->expectsOutput('CRON expression: */5 * * * *');
        $command->expectsOutput('Next runs (5):');
        $command->run();
    }

    #[Test]
    public function command_fails_with_non_existent_task_id(): void
    {
        $command = $this->pendingCommand('cron:test', ['id' => 999]);
        $command->assertExitCode(1);
        $command->expectsOutput('Task with ID 999 not found.');
        $command->run();
    }

    #[Test]
    public function command_handles_task_without_run_at_time(): void
    {
        $cronTaskFactory = CronTask::factory();
        $task = $cronTaskFactory->create(
            [
                'name' => 'Test Task Without Time',
                'frequency' => 'daily',
                'run_at' => null,
                'command' => ScheduledCommand::MONITOR_DATABASE,
            ],
        );

        $command = $this->pendingCommand('cron:test', ['id' => $task->id]);
        $command->expectsConfirmation('Do yu want to simulate task run? [y/N]', 'no');
        $command->assertExitCode(0);
        $command->expectsOutput("Testing of task: {$task->name}");
        $command->expectsOutput('CRON expression: 0 0 * * *'); // Should default to midnight
        $command->expectsOutput('Next runs (5):');
        $command->run();
    }

    #[Test]
    public function command_converts_unknown_frequency_to_default(): void
    {
        $cronTaskFactory = CronTask::factory();
        $task = $cronTaskFactory->create(
            [
                'name' => 'Test Unknown Frequency',
                'frequency' => 'unknown',
                'run_at' => '10:30:00',
                'command' => ScheduledCommand::MONITOR_DATABASE,
            ],
        );

        $command = $this->pendingCommand('cron:test', ['id' => $task->id]);
        $command->expectsConfirmation('Do yu want to simulate task run? [y/N]', 'no');
        $command->assertExitCode(0);
        $command->expectsOutput("Testing of task: {$task->name}");
        $command->expectsOutput('CRON expression: * * * * *'); // Default fallback
        $command->expectsOutput('Next runs (5):');
        $command->run();
    }

    #[Test]
    public function command_simulation_is_not_available_for_custom_expressions(): void
    {
        $command = $this->pendingCommand('cron:test', ['--expression' => '0 12 * * *']);
        $command->expectsConfirmation('Do yu want to simulate task run? [y/N]', 'yes');
        $command->expectsOutput(
            'Simulation is not accessible for testing of task expression.',
        );
        $command->assertExitCode(0);
        $command->run();
    }

    #[Test]
    public function command_can_simulate_task_execution(): void
    {
        Mail::fake();
        Notification::fake();
        Queue::fake();
        $cronTaskFactory = CronTask::factory();
        $task = $cronTaskFactory->create(
            [
                'name' => 'Test Simulation',
                'frequency' => 'daily',
                'run_at' => '12:00:00',
                'command' => ScheduledCommand::CHECK_INVOICE_PAYMENT_STATUS,
                'parameters' => '--days-before=5',
            ],
        );

        $command = $this->pendingCommand('cron:test', ['id' => $task->id]);
        $command->expectsConfirmation('Do yu want to simulate task run? [y/N]', 'yes');
        $command->expectsOutput('Task run: invoices:check-payment-status --days-before=5');
        $command->expectsOutputToContain('Notifications sent');
        $command->assertExitCode(0);
        $command->run();
    }

    #[Test]
    public function command_skips_simulation_when_declined(): void
    {
        $cronTaskFactory = CronTask::factory();
        $task = $cronTaskFactory->create(
            [
                'name' => 'Test No Simulation',
                'frequency' => 'daily',
                'run_at' => '12:00:00',
                'command' => ScheduledCommand::CHECK_INVOICE_PAYMENT_STATUS,
            ],
        );

        $command = $this->pendingCommand('cron:test', ['id' => $task->id]);
        $command->expectsConfirmation('Do yu want to simulate task run? [y/N]', 'no');
        $command->assertExitCode(0);
        $command->run();
    }
}
