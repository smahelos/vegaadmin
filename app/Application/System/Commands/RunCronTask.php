<?php

declare(strict_types=1);

namespace App\Application\System\Commands;

use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\CallsOutsideDatabaseInterface;
use App\Application\Shared\UseCase\Contracts\CommandInterface;
use App\Domain\System\ValueObjects\CronTaskId;
use App\Domain\User\Enums\Permission;

/**
 * Runs a cron task now, outside its schedule. The command may call outside the database, so only storing the run
 * is a transaction.
 *
 * @implements CommandInterface<null>
 */
final readonly class RunCronTask implements CallsOutsideDatabaseInterface, CommandInterface
{
    public function __construct(
        public CronTaskId $id,
    ) {}

    public static function access(): Access
    {
        return Access::permission(Permission::CRON_TASK_RUN);
    }
}
