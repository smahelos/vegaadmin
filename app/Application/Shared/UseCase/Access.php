<?php

declare(strict_types=1);

namespace App\Application\Shared\UseCase;

use App\Application\Shared\UseCase\Enums\AccessKind;
use App\Domain\User\Enums\Permission;

/**
 * Who may run a use case, declared once by the use case itself (rules/permissions.md).
 */
final readonly class Access
{
    /**
     * @param  list<Permission>  $permissions  any one of them suffices; empty for public and system
     */
    private function __construct(
        public AccessKind $kind,
        public array $permissions,
        public bool $withOwner,
    ) {}

    public static function permission(Permission $permission): self
    {
        return new self(
            AccessKind::PERMISSION,
            [$permission],
            false,
        );
    }

    /**
     * An admin writing for an owner needs the owner's permission too; a user acting on their own data is
     * checked once.
     */
    public static function permissionWithOwner(Permission $permission): self
    {
        return new self(
            AccessKind::PERMISSION,
            [$permission],
            true,
        );
    }

    public static function anyOf(Permission ...$permissions): self
    {
        return new self(
            AccessKind::PERMISSION,
            array_values($permissions),
            false,
        );
    }

    /**
     * An admin reading every owner's data; the handler gets AllOwnersScope.
     */
    public static function allOwners(Permission $permission): self
    {
        return new self(
            AccessKind::ALL_OWNERS,
            [$permission],
            false,
        );
    }

    /**
     * Anyone, an anonymous visitor included; reaches no owner's data.
     */
    public static function public(): self
    {
        return new self(
            AccessKind::PUBLIC,
            [],
            false,
        );
    }

    /**
     * Only Actor::system(); the handler gets AllOwnersScope.
     */
    public static function system(): self
    {
        return new self(
            AccessKind::SYSTEM,
            [],
            false,
        );
    }
}
