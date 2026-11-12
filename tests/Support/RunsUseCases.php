<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Application\Shared\UseCase\Actor;
use App\Application\Shared\UseCase\Contracts\AuthorizingUseCaseRunnerInterface;
use App\Application\Shared\UseCase\Contracts\CommandInterface;
use App\Application\Shared\UseCase\Contracts\QueryInterface;
use App\Domain\User\ValueObjects\UserId;

/**
 * Runs a use case through the real gate as a frontend user.
 */
trait RunsUseCases
{
    /**
     * @template TResult
     *
     * @param  CommandInterface<TResult>|QueryInterface<TResult>  $useCase
     * @return TResult
     */
    private function runAs(UserId $user, CommandInterface|QueryInterface $useCase): mixed
    {
        $useCases = $this->app->make(AuthorizingUseCaseRunnerInterface::class);

        return $useCases->run(
            Actor::user($user),
            $useCase,
        );
    }
}
