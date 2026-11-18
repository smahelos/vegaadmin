<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\System\Repositories;

use App\Domain\System\Contracts\CronTaskReadRepositoryInterface;
use App\Domain\System\Contracts\CronTaskWriteRepositoryInterface;
use App\Domain\System\DTO\CronTaskDTO;
use App\Domain\System\ValueObjects\CronTaskId;
use App\Infrastructure\Persistence\Eloquent\System\Mappers\EloquentCronTaskMapper;
use App\Models\CronTask;
use DateTimeImmutable;

final class EloquentCronTaskRepository implements CronTaskReadRepositoryInterface, CronTaskWriteRepositoryInterface
{
    public function findById(CronTaskId $id): ?CronTaskDTO
    {
        $task = CronTask::find($id->value);

        return $task === null ? null : EloquentCronTaskMapper::toDTO($task);
    }

    public function listActive(): array
    {
        $query = CronTask::where('is_active', true);
        $query->orderBy('id');
        $tasks = $query->get();
        $dtos = $tasks->map(
            fn (CronTask $task): CronTaskDTO => EloquentCronTaskMapper::toDTO($task),
        );

        return array_values(
            $dtos->all(),
        );
    }

    public function recordRun(
        CronTaskId $id,
        DateTimeImmutable $ran,
        ?string $output,
    ): void {
        $query = CronTask::whereKey($id->value);
        $query->update(
            [
                'last_run' => $ran,
                'last_output' => $output,
            ],
        );
    }
}
