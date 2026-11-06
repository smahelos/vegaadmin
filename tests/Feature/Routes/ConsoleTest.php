<?php

declare(strict_types=1);

namespace Tests\Feature\Routes;

use App\Domain\Shared\Console\Enums\ScheduledCommand;
use App\Models\CronTask;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule as ScheduleFacade;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

final class ConsoleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // The seeded invoice reminder task would be scheduled along with the tasks of each test
        $taskQuery = CronTask::query();
        $taskQuery->delete();
    }

    /**
     * @return array<string, array{string, ?int, ?int, string}>
     */
    public static function frequencies(): array
    {
        return [
            'daily' => [
                'daily',
                null,
                null,
                '30 7 * * *',
            ],
            'weekly' => [
                'weekly',
                3,
                null,
                '30 7 * * 3',
            ],
            'monthly' => [
                'monthly',
                null,
                15,
                '30 7 15 * *',
            ],
        ];
    }

    #[Test]
    #[DataProvider('frequencies')]
    public function schedules_a_task_at_its_stored_time(
        string $frequency,
        ?int $dayOfWeek,
        ?int $dayOfMonth,
        string $expression,
    ): void {
        $taskFactory = CronTask::factory();
        $taskFactory->create(
            [
                'frequency' => $frequency,
                'custom_expression' => null,
                'run_at' => '07:30:45',
                'day_of_week' => $dayOfWeek,
                'day_of_month' => $dayOfMonth,
                'is_active' => true,
            ],
        );

        $events = $this->scheduledEvents();
        $event = end($events);

        $this->assertSame($expression, $event->getExpression());
    }

    #[Test]
    public function logs_the_exception_when_loading_the_tasks_fails(): void
    {
        $failure = new RuntimeException('Database is down');
        $hasTable = Schema::shouldReceive('hasTable');
        $hasTable->andThrow($failure);
        $log = Log::spy();

        $events = $this->scheduledEvents();

        $this->assertCount(0, $events);
        $verification = $log->shouldHaveReceived('error');
        $verification = $verification->with(
            'Cron task loading failed',
            ['exception' => $failure],
        );
        $verification->once();
    }

    #[Test]
    public function schedules_nothing_but_the_active_tasks(): void
    {
        $taskFactory = CronTask::factory();
        $inactiveFactory = $taskFactory->inactive();
        $inactiveFactory->create();

        $events = $this->scheduledEvents();

        $this->assertSame([], $events);
    }

    #[Test]
    public function passes_the_options_as_separate_arguments(): void
    {
        $taskFactory = CronTask::factory();
        $activeFactory = $taskFactory->active();
        $commandFactory = $activeFactory->withCommand(
            ScheduledCommand::MONITOR_DATABASE,
            '--metric=all  --store',
        );
        $commandFactory->create();

        $events = $this->scheduledEvents();

        $this->assertCount(1, $events);
        $this->assertStringEndsWith(
            "'artisan' db:monitor --metric=all --store",
            $events[0]->command,
        );
    }

    #[Test]
    public function skips_a_task_whose_stored_options_could_reach_the_shell(): void
    {
        $taskFactory = CronTask::factory();
        $activeFactory = $taskFactory->active();
        $commandFactory = $activeFactory->withCommand(
            ScheduledCommand::MONITOR_DATABASE,
            '--metric=all;id',
        );
        $task = $commandFactory->create();
        $commandFactory = $activeFactory->withCommand(ScheduledCommand::CHECK_DATABASE_HEALTH);
        $commandFactory->create();
        $log = Log::spy();

        $events = $this->scheduledEvents();

        $this->assertCount(1, $events);
        $this->assertStringEndsWith("'artisan' db:health-check", $events[0]->command);
        $verification = $log->shouldHaveReceived('error');
        $verification = $verification->withArgs(
            fn (string $message, array $context): bool => $message === 'Cron task cannot be scheduled'
                && $context['cronTaskId'] === $task->id,
        );
        $verification->once();
    }

    /**
     * @return list<Event>
     */
    private function scheduledEvents(): array
    {
        $schedule = new Schedule;
        $this->app->instance(Schedule::class, $schedule);
        ScheduleFacade::clearResolvedInstance(Schedule::class);

        require base_path('routes/console.php');

        return $schedule->events();
    }
}
