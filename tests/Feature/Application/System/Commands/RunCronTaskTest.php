<?php

declare(strict_types=1);

namespace Tests\Feature\Application\System\Commands;

use App\Application\Shared\UseCase\Actor;
use App\Application\Shared\UseCase\Contracts\AuthorizingUseCaseRunnerInterface;
use App\Application\System\Commands\RunCronTask;
use App\Domain\System\Enums\ScheduledCommand;
use App\Domain\System\Exceptions\CronTaskFailedException;
use App\Domain\System\Exceptions\CronTaskNotFoundException;
use App\Domain\System\ValueObjects\CronTaskId;
use App\Domain\User\Enums\Permission;
use App\Domain\User\Exceptions\PermissionDeniedException;
use App\Domain\User\ValueObjects\UserId;
use App\Models\CronTask;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsBackendUsers;
use Tests\TestCase;

final class RunCronTaskTest extends TestCase
{
    use BuildsBackendUsers;

    #[Test]
    public function the_command_runs_with_its_options_and_the_run_is_stored(): void
    {
        Mail::fake();
        Notification::fake();
        Queue::fake();
        $task = $this->task('--days-before=5 --days-after=2');

        $this->runTask($task->id, Permission::CRON_TASK_RUN);

        $task->refresh();
        $this->assertNotNull($task->last_run);
        $this->assertStringContainsString('Notifications sent', $task->last_output);
    }

    #[Test]
    public function a_command_that_rejects_its_options_fails_and_the_run_is_stored(): void
    {
        $task = $this->task('--days-before=soon');

        try {
            $this->runTask($task->id, Permission::CRON_TASK_RUN);
            $this->fail('The run should have failed');
        } catch (CronTaskFailedException) {
        }

        $task->refresh();
        $this->assertNotNull($task->last_run);
    }

    #[Test]
    public function an_unknown_option_fails_and_its_error_is_stored(): void
    {
        $task = $this->task('--no-such-option');

        try {
            $this->runTask($task->id, Permission::CRON_TASK_RUN);
            $this->fail('The run should have failed');
        } catch (CronTaskFailedException) {
        }

        $task->refresh();
        $this->assertNotNull($task->last_run);
        $this->assertStringContainsString('no-such-option', $task->last_output);
    }

    #[Test]
    public function a_missing_task_is_not_found(): void
    {
        $this->expectException(CronTaskNotFoundException::class);

        $this->runTask(PHP_INT_MAX, Permission::CRON_TASK_RUN);
    }

    #[Test]
    public function an_admin_without_the_run_permission_is_denied_and_nothing_runs(): void
    {
        $task = $this->task('--days-before=5');

        try {
            $this->runTask($task->id, Permission::CRON_TASK_VIEW);
            $this->fail('The run should have been denied');
        } catch (PermissionDeniedException) {
        }

        $task->refresh();
        $this->assertNull($task->last_run);
    }

    private function task(string $parameters): CronTask
    {
        $cronTaskFactory = CronTask::factory();
        $commandFactory = $cronTaskFactory->withCommand(
            ScheduledCommand::CHECK_INVOICE_PAYMENT_STATUS,
            $parameters,
        );

        return $commandFactory->create(['last_run' => null]);
    }

    private function runTask(int $id, Permission $permission): void
    {
        $admin = $this->backendUser($permission);
        $useCases = $this->app->make(AuthorizingUseCaseRunnerInterface::class);
        $useCases->run(
            Actor::admin(
                UserId::fromInt($admin->id),
            ),
            new RunCronTask(
                CronTaskId::fromInt($id),
            ),
        );
    }
}
