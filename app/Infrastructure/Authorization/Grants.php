<?php

declare(strict_types=1);

namespace App\Infrastructure\Authorization;

use App\Domain\User\Enums\Permission;

/**
 * What one user may on one guard: the permissions held directly or through a role, and the guard's roles, whose
 * limit rows GrantStore reads only when a limit is checked (D-049).
 */
final readonly class Grants
{
    /**
     * @param  list<string>  $permissions  names of the permissions on the guard
     * @param  list<int>  $roles  ids of the roles on the guard
     */
    public function __construct(
        public array $permissions,
        public array $roles,
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
