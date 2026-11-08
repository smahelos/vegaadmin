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
     * Get all permission names for a user.
     *
     * @return array Array of permission names
     */
    public function getUserPermissions(int $userId, string $guard = 'web'): array;
}
