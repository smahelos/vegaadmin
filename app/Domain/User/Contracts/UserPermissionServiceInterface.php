<?php

namespace App\Domain\User\Contracts;

/**
 * Service contract for user permission operations.
 *
 * This interface allows Domain services to check user permissions
 * without depending on Eloquent models or Infrastructure details.
 * Implementation will be in Infrastructure layer.
 */
interface UserPermissionServiceInterface
{
    /**
     * Names of the permissions the user holds in the guard; each guard holds its own row of a name.
     *
     * @return list<string>
     */
    public function getUserPermissions(int $userId, string $guard): array;
}
