<?php

declare(strict_types=1);

namespace App\Infrastructure\Authorization\Services;

use App\Application\Shared\UseCase\Actor;
use App\Application\Shared\UseCase\Contracts\AuthorizingUseCaseRunnerInterface;
use App\Application\Shared\UseCase\Contracts\CallsOutsideDatabaseInterface;
use App\Application\Shared\UseCase\Contracts\CommandInterface;
use App\Application\Shared\UseCase\Contracts\QueryInterface;
use App\Application\Shared\UseCase\Enums\ActorKind;
use App\Application\Shared\UseCase\OwnedUseCase;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;
use App\Domain\Shared\Database\Contracts\TransactionBoundaryInterface;
use App\Domain\User\Enums\Permission;
use App\Domain\User\Exceptions\PermissionDeniedException;
use App\Domain\User\ValueObjects\UserId;
use App\Models\User;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Container\Container;
use LogicException;

/**
 * In order: the permission, the owner, the scope, the transaction, the handler (rules/permissions.md).
 */
final class AuthorizingUseCaseRunner implements AuthorizingUseCaseRunnerInterface
{
    public function __construct(
        private readonly Container $container,
        private readonly TransactionBoundaryInterface $transactions,
        private readonly AuthFactory $auth,
    ) {}

    public function run(Actor $actor, CommandInterface|QueryInterface $useCase): mixed
    {
        $access = $useCase::access();
        $this->requirePermission(
            $actor->user,
            $actor->kind,
            $access->permission,
        );

        $arguments = [$useCase];
        if ($useCase instanceof OwnedUseCase) {
            $this->checkOwner(
                $actor,
                $useCase->owner,
                $access->withOwner,
                $access->permission,
            );
            $arguments[] = $this->scope($useCase);
        }

        $handler = $this->handler($useCase);
        if ($useCase instanceof QueryInterface || $useCase instanceof CallsOutsideDatabaseInterface) {
            return $handler(...$arguments);
        }

        return $this->transactions->transaction(
            fn (): mixed => $handler(...$arguments),
        );
    }

    public function allowed(Actor $actor, string $useCase): bool
    {
        $guard = $actor->kind->guard();
        $guardDriver = $this->auth->guard($guard);
        // The signed-in instance remembers its roles, so a page asking many times loads them once
        $user = $guardDriver->user();
        if (!$user instanceof User || $user->getKey() !== $actor->user->toInt()) {
            return false;
        }
        $access = $useCase::access();

        return $user->checkPermissionTo($access->permission->value, $guard);
    }

    private function checkOwner(
        Actor $actor,
        UserId $owner,
        bool $withOwner,
        Permission $permission,
    ): void {
        if ($actor->kind === ActorKind::USER) {
            // A user reaches only their own data; delivery passes the signed-in user as the owner
            if ($owner->toInt() !== $actor->user->toInt()) {
                throw new PermissionDeniedException($permission);
            }

            return;
        }

        if ($withOwner) {
            $this->requirePermission(
                $owner,
                ActorKind::USER,
                $permission,
            );
        }
    }

    private function requirePermission(
        UserId $userId,
        ActorKind $kind,
        Permission $permission,
    ): void {
        $user = User::find($userId->toInt());
        if (!$user instanceof User || !$user->checkPermissionTo($permission->value, $kind->guard())) {
            throw new PermissionDeniedException($permission);
        }
    }

    private function scope(OwnedUseCase $useCase): ReadScope|WriteScope
    {
        $read = ReadScope::issue($useCase->owner);
        if ($useCase instanceof QueryInterface) {
            return $read;
        }

        return WriteScope::issue($useCase->owner, $read);
    }

    /**
     * The handler is the class named after the use case with the suffix Handler; a Larastan rule checks that it
     * exists and returns the declared result (phpstan/Rules/UseCaseResultRule.php).
     */
    private function handler(CommandInterface|QueryInterface $useCase): callable
    {
        $handler = $this->container->make($useCase::class . 'Handler');
        $handle = [$handler, 'handle'];
        if (!is_callable($handle)) {
            throw new LogicException(
                'The handler of ' . $useCase::class . ' has no handle method',
            );
        }

        return $handle;
    }
}
