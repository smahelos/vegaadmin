<?php

namespace App\Application\User\Contracts;

/**
 * Application layer facade for user-related authorization operations
 * Controllers should depend on this interface, not on Domain services.
 */
interface UserAuthorizationAdapterInterface
{
    /**
     * Check if user can access any clients.
     */
    public function canAccessAnyClients(int $userId): bool;

    /**
     * Check if user can access any suppliers.
     */
    public function canAccessAnySuppliers(int $userId): bool;

    /**
     * Check if user has web admin role.
     */
    public function isWebAdmin(int $userId): bool;

    /**
     * Check if user has backpack admin role.
     */
    public function isBackpackAdmin(int $userId): bool;

    /**
     * Check if user has specific permission in backpack guard.
     */
    public function hasBackpackPermission(int $userId, string $permission): bool;

    /**
     * Summary of hasBackpackViewPermission
     */
    public function hasBackpackViewPermission(int $userId, string $entityType): bool;

    /**
     * Check if user has frontend create/edit permission.
     */
    public function hasCreateEditPermission(int $userId): bool;

    /**
     * Check if user has frontend user role.
     */
    public function hasFrontendUserRole(int $userId): bool;
}
