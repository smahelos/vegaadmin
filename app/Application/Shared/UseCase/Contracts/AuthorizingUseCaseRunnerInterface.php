<?php

declare(strict_types=1);

namespace App\Application\Shared\UseCase\Contracts;

use App\Application\Shared\UseCase\Actor;

/**
 * The gate every use case runs through: permission, owner, scope, transaction, handler (rules/permissions.md).
 */
interface AuthorizingUseCaseRunnerInterface
{
    /**
     * @template TResult
     *
     * @param  CommandInterface<TResult>|QueryInterface<TResult>  $useCase
     * @return TResult
     */
    public function run(Actor $actor, CommandInterface|QueryInterface $useCase): mixed;

    /**
     * Whether the actor holds the permission the use case declares; only for hiding what the actor cannot do.
     *
     * @param  class-string<CommandInterface<mixed>|QueryInterface<mixed>>  $useCase
     */
    public function allowed(Actor $actor, string $useCase): bool;
}
