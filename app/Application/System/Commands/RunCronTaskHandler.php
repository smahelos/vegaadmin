<?php

declare(strict_types=1);

namespace App\Application\System\Commands;

use App\Domain\Shared\Database\Contracts\TransactionBoundaryInterface;
use App\Domain\Shared\Time\Contracts\ClockInterface;
use App\Domain\System\Contracts\CommandRunnerInterface;
use App\Domain\System\Contracts\CronTaskReadRepositoryInterface;
use App\Domain\System\Contracts\CronTaskWriteRepositoryInterface;
use App\Domain\System\Exceptions\CommandFailedException;
use App\Domain\System\Exceptions\CronTaskFailedException;
use App\Domain\System\Exceptions\CronTaskNotFoundException;
use App\Domain\System\Exceptions\InvalidCommandOptionsException;
use App\Domain\System\ValueObjects\CommandOptions;
use App\Domain\System\ValueObjects\CronTaskId;

final class RunCronTaskHandler
{
    public function __construct(
        private readonly CronTaskReadRepositoryInterface $taskReads,
        private readonly CronTaskWriteRepositoryInterface $taskWrites,
        private readonly CommandRunnerInterface $commands,
        private readonly TransactionBoundaryInterface $transactions,
        private readonly ClockInterface $clock,
    ) {}

    /**
     * Stores the time and output of the run, also of a failed one.
     *
     * @throws CronTaskFailedException
     */
    public function handle(RunCronTask $command): void
    {
        $task = $this->taskReads->findById($command->id);
        if ($task === null) {
            throw new CronTaskNotFoundException($command->id);
        }

        try {
            $options = CommandOptions::fromString($task->parameters);
            $run = $this->commands->run($task->command, $options);
        } catch (InvalidCommandOptionsException|CommandFailedException $e) {
            $this->recordRun($task->id, $e->getMessage());

            throw new CronTaskFailedException(
                "Cron task {$task->id->value} failed",
                0,
                $e,
            );
        }

        $this->recordRun($task->id, $run->output);
        if (!$run->succeeded()) {
            throw new CronTaskFailedException("Cron task {$task->id->value} exited with {$run->exitCode}");
        }
    }

    private function recordRun(CronTaskId $id, string $output): void
    {
        $this->transactions->transaction(
            fn () => $this->taskWrites->recordRun(
                $id,
                $this->clock->now(),
                $output,
            ),
        );
    }
}
