<?php

declare(strict_types=1);

namespace App\Domain\System\Contracts;

use App\Domain\System\DTO\CommandRunDTO;
use App\Domain\System\Enums\ScheduledCommand;
use App\Domain\System\Exceptions\CommandFailedException;
use App\Domain\System\ValueObjects\CommandOptions;

interface CommandRunnerInterface
{
    /**
     * Runs the command in this process and returns its exit code and output.
     *
     * @throws CommandFailedException when the command throws
     */
    public function run(ScheduledCommand $command, CommandOptions $options): CommandRunDTO;
}
