<?php

declare(strict_types=1);

namespace App\Infrastructure\Authorization;

use App\Domain\User\DTO\RoleLimitDTO;
use App\Domain\User\Enums\Permission;

/**
 * What one user may on one guard: the permissions held directly or through a role, and the limit rows of the
 * guard's roles (D-049).
 */
final readonly class Grants
{
    /**
     * @param  list<string>  $permissions  names of the permissions on the guard
     * @param  list<RoleLimitDTO>  $limits
     */
    public function __construct(
        private array $permissions,
        public array $limits,
    ) {}

    public function holds(Permission $permission): bool
    {
        return in_array(
            $permission->value,
            $this->permissions,
            true,
        );
    }
}
