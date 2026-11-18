<?php

declare(strict_types=1);

namespace App\Infrastructure\Console\System;

use App\Domain\System\Contracts\CommandRunnerInterface;
use App\Domain\System\DTO\CommandRunDTO;
use App\Domain\System\Enums\ScheduledCommand;
use App\Domain\System\Exceptions\CommandFailedException;
use App\Domain\System\ValueObjects\CommandOptions;
use Illuminate\Support\Facades\Artisan;
use Throwable;

final class ArtisanCommandRunner implements CommandRunnerInterface
{
    public function run(ScheduledCommand $command, CommandOptions $options): CommandRunDTO
    {
        // Whatever the command throws is its failure, reported to the caller with the original exception
        try {
            $exitCode = Artisan::call(
                $command->value,
                $options->input(),
            );
        } catch (Throwable $e) {
            throw new CommandFailedException(
                $e->getMessage(),
                0,
                $e,
            );
        }

        return new CommandRunDTO(
            $exitCode,
            Artisan::output(),
        );
    }
}
