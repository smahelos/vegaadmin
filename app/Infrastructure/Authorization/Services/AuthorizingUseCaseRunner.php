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
        private readonly GrantStore $grants,
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
        if ($access->kind === AccessKind::SYSTEM) {
            return false;
        }
        // Same rule as permits(): only an admin reads every owner's data
        if ($access->kind === AccessKind::ALL_OWNERS && $actor->kind !== ActorKind::ADMIN) {
            return false;
        }

        // The same grants run() reads, so it answers for the actor it is given, signed in or not (D-049)
        return $this->actorHoldsAnyPermission($actor, $access);
    }

    public function holds(Actor $actor, Permission $permission): bool
    {
        return $this->holdsAnyPermission(
            $actor->user,
            $actor->kind,
            [$permission],
        );
    }

    private function permits(Actor $actor, Access $access): bool
    {
        return match ($access->kind) {
            AccessKind::PUBLIC => true,
            AccessKind::SYSTEM => $actor->kind === ActorKind::SYSTEM,
            AccessKind::ALL_OWNERS => $actor->kind === ActorKind::ADMIN && $this->actorHoldsAnyPermission($actor, $access),
            AccessKind::PERMISSION => $this->actorHoldsAnyPermission($actor, $access),
        };
    }

    private function actorHoldsAnyPermission(Actor $actor, Access $access): bool
    {
        return $this->holdsAnyPermission(
            $actor->user,
            $actor->kind,
            $access->permissions,
        );
    }

    /**
     * @param  list<Permission>  $permissions
     */
    private function holdsAnyPermission(
        ?UserId $user,
        ActorKind $kind,
        array $permissions,
    ): bool {
        $guard = $kind->guard();
        if ($user === null || $guard === null) {
            return false;
        }
        $grants = $this->grants->for($user, $guard);
        foreach ($permissions as $permission) {
            if ($grants->holds($permission)) {
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
            ActorKind::USER => $owner->value === $actor->user?->value,
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
            $owner,
            ActorKind::USER,
            $permissions,
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
