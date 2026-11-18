<?php

declare(strict_types=1);

namespace App\Application\System\Queries;

use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\QueryInterface;
use App\Domain\System\DTO\CronTaskDTO;

/**
 * The tasks the scheduler registers.
 *
 * @implements QueryInterface<list<CronTaskDTO>>
 */
final readonly class ListActiveCronTasks implements QueryInterface
{
    public static function access(): Access
    {
        return Access::system();
    }
}
