<?php

namespace Tests\Feature\Console\Commands;

use App\Models\CronTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TestCronExpressionFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function command_exists_and_has_correct_signature(): void
    {
        $this->artisan('cron:test --help')
            ->assertExitCode(0);
    }

    #[Test]
    public function command_fails_without_arguments(): void
    {
        $this->artisan('cron:test')
            ->assertExitCode(1)
            ->expectsOutput('You have to set task ID or CRON expression.');
    }

    #[Test]
    public function command_tests_custom_expression(): void
    {
        $this->artisan('cron:test', ['--expression' => '0 12 * * *'])
            ->expectsConfirmation('Do yu want to simulate task run? [y/N]', 'no')
            ->assertExitCode(0)
            ->expectsOutput('CRON expression testing: 0 12 * * *')
            ->expectsOutput('Next runs (5):');
    }

    #[Test]
    public function command_rejects_invalid_expression(): void
    {
        $this->artisan('cron:test', ['--expression' => 'invalid'])
            ->assertExitCode(1)
            ->expectsOutputToContain('Incorrect CRON expression:');
    }

    #[Test]
    public function command_tests_existing_task_with_daily_frequency(): void
    {
        $task = CronTask::factory()->create([
            'name' => 'Test Daily Task',
            'frequency' => 'daily',
            'run_at' => '12:30:00',
            'command' => 'test:command'
        ]);

        $this->artisan('cron:test', ['id' => $task->id])
            ->expectsConfirmation('Do yu want to simulate task run? [y/N]', 'no')
            ->assertExitCode(0)
            ->expectsOutput("Testing of task: {$task->name}")
            ->expectsOutput('CRON expression: 30 12 * * *')
            ->expectsOutput('Next runs (5):');
    }

    #[Test]
    public function command_tests_existing_task_with_weekly_frequency(): void
    {
        $task = CronTask::factory()->create([
            'name' => 'Test Weekly Task',
            'frequency' => 'weekly',
            'run_at' => '09:15:00',
            'day_of_week' => 1, // Monday
            'command' => 'test:command'
        ]);

        $this->artisan('cron:test', ['id' => $task->id])
            ->expectsConfirmation('Do yu want to simulate task run? [y/N]', 'no')
            ->assertExitCode(0)
            ->expectsOutput("Testing of task: {$task->name}")
            ->expectsOutput('CRON expression: 15 9 * * 1')
            ->expectsOutput('Next runs (5):');
    }

    #[Test]
    public function command_tests_existing_task_with_monthly_frequency(): void
    {
        $task = CronTask::factory()->create([
            'name' => 'Test Monthly Task',
            'frequency' => 'monthly',
            'run_at' => '08:00:00',
            'day_of_month' => 15,
            'command' => 'test:command'
        ]);

        $this->artisan('cron:test', ['id' => $task->id])
            ->expectsConfirmation('Do yu want to simulate task run? [y/N]', 'no')
            ->assertExitCode(0)
            ->expectsOutput("Testing of task: {$task->name}")
            ->expectsOutput('CRON expression: 0 8 15 * *')
            ->expectsOutput('Next runs (5):');
    }

    #[Test]
    public function command_tests_existing_task_with_custom_expression(): void
    {
        $task = CronTask::factory()->create([
            'name' => 'Test Custom Task',
            'frequency' => 'custom',
            'custom_expression' => '*/5 * * * *',
            'command' => 'test:command'
        ]);

        $this->artisan('cron:test', ['id' => $task->id])
            ->expectsConfirmation('Do yu want to simulate task run? [y/N]', 'no')
            ->assertExitCode(0)
            ->expectsOutput("Testing of task: {$task->name}")
            ->expectsOutput('CRON expression: */5 * * * *')
            ->expectsOutput('Next runs (5):');
    }

    #[Test]
    public function command_fails_with_non_existent_task_id(): void
    {
        $this->artisan('cron:test', ['id' => 999])
            ->assertExitCode(1)
            ->expectsOutput('Task with ID 999 not found.');
    }

    #[Test]
    public function command_handles_task_without_run_at_time(): void
    {
        $task = CronTask::factory()->create([
            'name' => 'Test Task Without Time',
            'frequency' => 'daily',
            'run_at' => null,
            'command' => 'test:command'
        ]);

        $this->artisan('cron:test', ['id' => $task->id])
            ->expectsConfirmation('Do yu want to simulate task run? [y/N]', 'no')
            ->assertExitCode(0)
            ->expectsOutput("Testing of task: {$task->name}")
            ->expectsOutput('CRON expression: 0 0 * * *') // Should default to midnight
            ->expectsOutput('Next runs (5):');
    }

    #[Test]
    public function command_converts_unknown_frequency_to_default(): void
    {
        $task = CronTask::factory()->create([
            'name' => 'Test Unknown Frequency',
            'frequency' => 'unknown',
            'run_at' => '10:30:00',
            'command' => 'test:command'
        ]);

        $this->artisan('cron:test', ['id' => $task->id])
            ->expectsConfirmation('Do yu want to simulate task run? [y/N]', 'no')
            ->assertExitCode(0)
            ->expectsOutput("Testing of task: {$task->name}")
            ->expectsOutput('CRON expression: * * * * *') // Default fallback
            ->expectsOutput('Next runs (5):');
    }

    #[Test]
    public function command_simulation_is_not_available_for_custom_expressions(): void
    {
        $this->artisan('cron:test', ['--expression' => '0 12 * * *'])
            ->expectsConfirmation('Do yu want to simulate task run? [y/N]', 'yes')
            ->expectsOutput('Simulation is not accessible for testing of task expression.')
            ->assertExitCode(0);
    }

    #[Test]
    public function command_can_simulate_task_execution(): void
    {
        $task = CronTask::factory()->create([
            'name' => 'Test Simulation',
            'frequency' => 'daily',
            'run_at' => '12:00:00',
            'command' => 'list' // Use an existing artisan command
        ]);

        $this->artisan('cron:test', ['id' => $task->id])
            ->expectsConfirmation('Do yu want to simulate task run? [y/N]', 'yes')
            ->expectsOutput("Task run: {$task->command}")
            ->assertExitCode(0);
    }

    #[Test]
    public function command_skips_simulation_when_declined(): void
    {
        $task = CronTask::factory()->create([
            'name' => 'Test No Simulation',
            'frequency' => 'daily',
            'run_at' => '12:00:00',
            'command' => 'list'
        ]);

        $this->artisan('cron:test', ['id' => $task->id])
            ->expectsConfirmation('Do yu want to simulate task run? [y/N]', 'no')
            ->assertExitCode(0);
    }
}
