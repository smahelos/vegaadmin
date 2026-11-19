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
use Illuminate\Support\Facades\Log;
use ValueError;

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
        $dtos = [];
        foreach ($tasks as $task) {
            // A stored command or frequency that the enums no longer offer (ValueError) skips only its task
            try {
                $dtos[] = EloquentCronTaskMapper::toDTO($task);
            } catch (ValueError $e) {
                Log::error(
                    'Cron task cannot be read',
                    ['cronTaskId' => $task->id, 'exception' => $e],
                );
            }
        }

        return $dtos;
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
