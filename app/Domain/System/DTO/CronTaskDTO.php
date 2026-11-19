<?php

declare(strict_types=1);

namespace App\Domain\System\DTO;

use App\Domain\System\Enums\CronTaskFrequency;
use App\Domain\System\Enums\ScheduledCommand;
use App\Domain\System\ValueObjects\CronTaskId;
use DateTimeImmutable;

final readonly class CronTaskDTO
{
    /**
     * @param  string  $parameters  command options as entered, parsed by CommandOptions
     * @param  DateTimeImmutable|null  $runAt  only its time of day is used
     */
    public function __construct(
        public CronTaskId $id,
        public ScheduledCommand $command,
        public string $parameters,
        public CronTaskFrequency $frequency,
        public ?string $customExpression,
        public ?DateTimeImmutable $runAt,
        public ?int $dayOfWeek,
        public ?int $dayOfMonth,
    ) {}
}
