<?php

declare(strict_types=1);

namespace App\Application\System\Queries;

use App\Domain\System\Contracts\CronTaskReadRepositoryInterface;
use App\Domain\System\DTO\CronTaskDTO;

final class ListActiveCronTasksHandler
{
    public function __construct(
        private readonly CronTaskReadRepositoryInterface $tasks,
    ) {}

    /**
     * @return list<CronTaskDTO>
     */
    public function handle(ListActiveCronTasks $query): array
    {
        return $this->tasks->listActive();
    }
}
