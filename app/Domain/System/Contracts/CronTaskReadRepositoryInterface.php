<?php

declare(strict_types=1);

namespace App\Domain\System\Contracts;

use App\Domain\System\DTO\CronTaskDTO;
use App\Domain\System\ValueObjects\CronTaskId;

interface CronTaskReadRepositoryInterface
{
    public function findById(CronTaskId $id): ?CronTaskDTO;

    /** @return list<CronTaskDTO> without the tasks whose stored command or frequency the enums no longer offer */
    public function listActive(): array;
}
