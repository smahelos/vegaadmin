<?php

declare(strict_types=1);

namespace App\Application\System\Commands;

use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\CommandInterface;
use App\Domain\System\ValueObjects\CronTaskId;

/**
 * Stores the time and output of a scheduled run, after the scheduler ran the task's command.
 *
 * @implements CommandInterface<null>
 */
final readonly class RecordCronTaskRun implements CommandInterface
{
    public function __construct(
        public CronTaskId $id,
        public ?string $output,
    ) {}

    public static function access(): Access
    {
        return Access::system();
    }
}
