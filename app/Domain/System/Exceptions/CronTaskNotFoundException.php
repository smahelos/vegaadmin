<?php

declare(strict_types=1);

namespace App\Domain\System\Exceptions;

use App\Domain\System\ValueObjects\CronTaskId;
use DomainException;

final class CronTaskNotFoundException extends DomainException
{
    public function __construct(CronTaskId $id)
    {
        parent::__construct("Cron task {$id->value} not found");
    }
}
