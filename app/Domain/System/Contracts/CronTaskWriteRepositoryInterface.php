<?php

declare(strict_types=1);

namespace App\Domain\System\Contracts;

use App\Domain\System\ValueObjects\CronTaskId;
use DateTimeImmutable;

interface CronTaskWriteRepositoryInterface
{
    public function recordRun(
        CronTaskId $id,
        DateTimeImmutable $ran,
        ?string $output,
    ): void;
}
