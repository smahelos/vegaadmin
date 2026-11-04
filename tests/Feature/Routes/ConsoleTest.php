<?php

declare(strict_types=1);

namespace Tests\Feature\Routes;

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

        $this->assertCount(1, $events);
        $verification = $log->shouldHaveReceived('error');
        $verification = $verification->with(
            'Cron task loading failed',
            ['exception' => $failure],
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
