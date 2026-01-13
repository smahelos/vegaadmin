<?php

namespace App\Infrastructure\Authorization\Adapters\User;

use App\Application\User\Contracts\UserAuthorizationAdapterInterface;
use App\Models\User;

/**
 * Infrastructure adapter that uses Spatie permissions and Backpack guard
 * to implement application-level UserAuthorizationAdapterInterface.
 */
class BackpackSpatieAuthorizationAdapter implements UserAuthorizationAdapterInterface
{
    public function isWebAdmin(int $userId): bool
    {
        $user = User::query()->find($userId);
        if (!$user || !method_exists($user, 'hasRole')) {
            return false;
        }
        try {
            return (bool) $user->hasRole('admin');
        } catch (\Throwable) {
            return false;
        }
    }

    public function isBackpackAdmin(int $userId): bool
    {
        $user = User::query()->find($userId);
        if (!$user || !method_exists($user, 'hasRole')) {
            return false;
        }
        try {
            return (bool) $user->hasRole('admin', 'backpack');
        } catch (\Throwable) {
            return false;
        }
    }

    public function hasBackpackPermission(int $userId, string $permission): bool
    {
        $user = User::query()->find($userId);
        if (!$user || !method_exists($user, 'hasPermissionTo')) {
            return false;
        }
        try {
            return (bool) $user->hasPermissionTo($permission, 'backpack');
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Check if user can access any clients (admin or permission-based).
     */
    public function canAccessAnyClients(int $userId): bool
    {
        $user = User::query()->find($userId);
        return $this->isWebAdmin($userId) || 
               $this->isBackpackAdmin($userId) || 
               $this->hasBackpackViewPermission($userId, 'client');
    }

    /**
     * Check if user can access any suppliers (admin or permission-based).
     */
    public function canAccessAnySuppliers(int $userId): bool
    {
        $user = User::query()->find($userId);
        return $this->isWebAdmin($userId) || 
               $this->isBackpackAdmin($userId) || 
               $this->hasBackpackViewPermission($userId, 'supplier');
    }

    /**
     * Check if user has specific view permission in backpack guard.
     * Uses safe try/catch to handle guard compatibility issues.
     */
    public function hasBackpackViewPermission(int $userId, string $entityType): bool
    {
        $user = User::query()->find($userId);
        if (!method_exists($user, 'hasPermissionTo')) {
            return false;
        }
        
        try {
            return $user->hasPermissionTo("can_view_{$entityType}", 'backpack');
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Check if user has frontend create/edit permission.
     */
    public function hasCreateEditPermission(int $userId): bool
    {
        $user = User::query()->find($userId);
        if (!method_exists($user, 'hasPermissionTo')) {
            return false;
        }

        try {
            return $user->hasPermissionTo('frontend.can_create_edit_product');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if user has frontend user role.
     */
    public function hasFrontendUserRole(int $userId): bool
    {
        $user = User::query()->find($userId);
        if (!method_exists($user, 'hasRole')) {
            return false;
        }

        try {
            return $user->hasRole('frontend_user') || 
                   $user->hasRole('frontend_user_plus');
        } catch (\Exception $e) {
            return false;
        }
    }
}
