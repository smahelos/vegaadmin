<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\System\Mappers;

use App\Domain\System\DTO\CronTaskDTO;
use App\Domain\System\Enums\CronTaskFrequency;
use App\Domain\System\ValueObjects\CronTaskId;
use App\Models\CronTask;
use DateTimeImmutable;

final class EloquentCronTaskMapper
{
    public static function toDTO(CronTask $task): CronTaskDTO
    {
        $runAt = null;
        if ($task->run_at !== null) {
            $runAt = DateTimeImmutable::createFromInterface($task->run_at);
        }

        return new CronTaskDTO(
            id: CronTaskId::fromInt($task->id),
            command: $task->command,
            parameters: $task->parameters ?? '',
            frequency: CronTaskFrequency::from($task->frequency),
            customExpression: $task->custom_expression,
            runAt: $runAt,
            dayOfWeek: $task->day_of_week,
            dayOfMonth: $task->day_of_month,
        );
    }
}
