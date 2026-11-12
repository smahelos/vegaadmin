<?php

declare(strict_types=1);

namespace App\Infrastructure\Authorization\Services;

use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Actor;
use App\Application\Shared\UseCase\Contracts\AuthorizingUseCaseRunnerInterface;
use App\Application\Shared\UseCase\Contracts\CallsOutsideDatabaseInterface;
use App\Application\Shared\UseCase\Contracts\CommandInterface;
use App\Application\Shared\UseCase\Contracts\QueryInterface;
use App\Application\Shared\UseCase\Enums\AccessKind;
use App\Application\Shared\UseCase\Enums\ActorKind;
use App\Application\Shared\UseCase\OwnedUseCase;
use App\Domain\Shared\Authorization\ValueObjects\AllOwnersScope;
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
        if (!$this->permits($actor, $access)) {
            throw new PermissionDeniedException($useCase::class);
        }

        $arguments = [$useCase];
        if ($useCase instanceof OwnedUseCase) {
            $this->checkOwner(
                $actor,
                $useCase,
                $access,
            );
            $arguments[] = $this->scope($useCase);
        } elseif ($access->kind === AccessKind::ALL_OWNERS || $access->kind === AccessKind::SYSTEM) {
            $arguments[] = AllOwnersScope::issue();
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
        $access = $useCase::access();
        if ($access->kind === AccessKind::PUBLIC) {
            return true;
        }
        $guard = $actor->kind->guard();
        if ($guard === null || $access->kind === AccessKind::SYSTEM) {
            return false;
        }
        // Same rule as permits(): only an admin reads every owner's data
        if ($access->kind === AccessKind::ALL_OWNERS && $actor->kind !== ActorKind::ADMIN) {
            return false;
        }
        $guardDriver = $this->auth->guard($guard);
        // The signed-in instance remembers its roles, so a page asking many times loads them once
        $user = $guardDriver->user();
        if (!$user instanceof User || $user->getKey() !== $actor->user?->toInt()) {
            return false;
        }

        return $this->holdsAnyPermission(
            $user,
            $actor->kind,
            $access,
        );
    }

    private function permits(Actor $actor, Access $access): bool
    {
        return match ($access->kind) {
            AccessKind::PUBLIC => true,
            AccessKind::SYSTEM => $actor->kind === ActorKind::SYSTEM,
            AccessKind::ALL_OWNERS => $actor->kind === ActorKind::ADMIN && $this->userHoldsAnyPermission($actor, $access),
            AccessKind::PERMISSION => $this->userHoldsAnyPermission($actor, $access),
        };
    }

    private function userHoldsAnyPermission(Actor $actor, Access $access): bool
    {
        if ($actor->user === null) {
            return false;
        }

        return $this->holdsAnyPermission(
            User::find($actor->user->toInt()),
            $actor->kind,
            $access,
        );
    }

    private function holdsAnyPermission(
        ?User $user,
        ActorKind $kind,
        Access $access,
    ): bool {
        $guard = $kind->guard();
        if (!$user instanceof User || $guard === null) {
            return false;
        }
        foreach ($access->permissions as $permission) {
            if ($user->checkPermissionTo($permission->value, $guard)) {
                return true;
            }
        }

        return false;
    }

    private function checkOwner(
        Actor $actor,
        OwnedUseCase $useCase,
        Access $access,
    ): void {
        $owner = $useCase->owner;
        $allowed = match ($actor->kind) {
            // A user reaches only their own data; delivery passes the signed-in user as the owner
            ActorKind::USER => $owner->toInt() === $actor->user?->toInt(),
            ActorKind::ADMIN => !$access->withOwner || $this->ownerHoldsPermission($owner, $access->permissions),
            // Without a user the owner would come from input; no owned use case is public or system yet
            ActorKind::ANONYMOUS, ActorKind::SYSTEM => false,
        };
        if (!$allowed) {
            throw new PermissionDeniedException($useCase::class);
        }
    }

    /**
     * @param  list<Permission>  $permissions
     */
    private function ownerHoldsPermission(UserId $owner, array $permissions): bool
    {
        return $this->holdsAnyPermission(
            User::find($owner->toInt()),
            ActorKind::USER,
            Access::anyOf(...$permissions),
        );
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
