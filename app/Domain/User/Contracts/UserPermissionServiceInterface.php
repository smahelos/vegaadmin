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
     * @param int $userId The user ID
     * @param string $guard The guard name (default: 'web')
     * @return array Array of permission names
     */
    public function getUserPermissions(int $userId, string $guard = 'web'): array;

    /**
     * Check if user has specific permission.
     * 
     * @param int $userId The user ID
     * @param string $permission Permission name
     * @param string $guard The guard name (default: 'web')
     * @return bool True if user has permission
     */
    public function hasPermission(int $userId, string $permission, string $guard = 'web'): bool;

    /**
     * Check if user has any of the given permissions.
     * 
     * @param int $userId The user ID
     * @param array $permissions Array of permission names
     * @param string $guard The guard name (default: 'web')
     * @return bool True if user has at least one permission
     */
    public function hasAnyPermission(int $userId, array $permissions, string $guard = 'web'): bool;

    /**
     * Check if user exists.
     * 
     * @param int $userId The user ID
     * @return bool True if user exists
     */
    public function userExists(int $userId): bool;
}
