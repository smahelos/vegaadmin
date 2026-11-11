<?php

declare(strict_types=1);

namespace App\Application\Shared\UseCase;

use App\Domain\User\Enums\Permission;

/**
 * Who may run a use case, declared once by the use case itself (rules/permissions.md).
 */
final readonly class Access
{
    private function __construct(
        public Permission $permission,
        public bool $withOwner,
    ) {}

    public static function permission(Permission $permission): self
    {
        return new self($permission, false);
    }

    /**
     * An admin writing for an owner needs the owner's permission too; a user acting on their own data is
     * checked once.
     */
    public static function permissionWithOwner(Permission $permission): self
    {
        return new self($permission, true);
    }
}
