<?php

namespace App\Application\User\Contracts;

use App\Domain\User\Enums\Permission;

/**
 * Application layer facade for user-related authorization operations
 * Controllers should depend on this interface, not on Domain services.
 */
interface UserAuthorizationAdapterInterface
{
    /**
     * Check if user has specific permission in backpack guard.
     */
    public function hasBackpackPermission(int $userId, Permission $permission): bool;
}
