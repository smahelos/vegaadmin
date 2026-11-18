<?php

declare(strict_types=1);

namespace App\Application\System\Commands;

use App\Domain\Shared\Time\Contracts\ClockInterface;
use App\Domain\System\Contracts\CronTaskWriteRepositoryInterface;

final class RecordCronTaskRunHandler
{
    public function __construct(
        private readonly CronTaskWriteRepositoryInterface $tasks,
        private readonly ClockInterface $clock,
    ) {}

    public function handle(RecordCronTaskRun $command): void
    {
        $this->tasks->recordRun(
            $command->id,
            $this->clock->now(),
            $command->output,
        );
    }
}
