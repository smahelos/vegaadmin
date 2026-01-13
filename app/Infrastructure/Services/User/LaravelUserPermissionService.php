<?php

namespace App\Infrastructure\Services\User;

use App\Domain\User\Contracts\UserPermissionServiceInterface;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Infrastructure implementation of UserPermissionServiceInterface.
 * 
 * Handles Eloquent operations and permission checking using Spatie Permission package.
 * This keeps all Framework-specific logic out of Domain layer.
 */
class LaravelUserPermissionService implements UserPermissionServiceInterface
{
    /**
     * Cache key prefix for user permissions
     */
    private const CACHE_PREFIX = 'user_permissions_';

    /**
     * Cache TTL in seconds (30 minutes)
     */
    private const CACHE_TTL = 1800;

    /**
     * Get all permission names for a user.
     */
    public function getUserPermissions(int $userId, string $guard = 'web'): array
    {
        $cacheKey = self::CACHE_PREFIX . "{$userId}_{$guard}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId, $guard) {
            $user = User::find($userId);
            
            if (!$user) {
                Log::debug('UserPermissionService: User not found', [
                    'user_id' => $userId,
                    'guard' => $guard
                ]);
                return [];
            }

            $permissions = $user->getAllPermissions();
            $permissionNames = $permissions->pluck('name')->unique()->values()->toArray();
            
            Log::debug('UserPermissionService: Retrieved user permissions', [
                'user_id' => $userId,
                'guard' => $guard,
                'permissions_count' => count($permissionNames)
            ]);

            return $permissionNames;
        });
    }

    /**
     * Check if user has specific permission.
     */
    public function hasPermission(int $userId, string $permission, string $guard = 'web'): bool
    {
        $user = User::find($userId);
        
        if (!$user) {
            Log::debug('UserPermissionService: User not found for permission check', [
                'user_id' => $userId,
                'permission' => $permission,
                'guard' => $guard
            ]);
            return false;
        }

        $hasPermission = $user->hasPermissionTo($permission, $guard);
        
        Log::debug('UserPermissionService: Permission check result', [
            'user_id' => $userId,
            'permission' => $permission,
            'guard' => $guard,
            'has_permission' => $hasPermission
        ]);

        return $hasPermission;
    }

    /**
     * Check if user has any of the given permissions.
     */
    public function hasAnyPermission(int $userId, array $permissions, string $guard = 'web'): bool
    {
        $user = User::find($userId);
        
        if (!$user) {
            Log::debug('UserPermissionService: User not found for any permission check', [
                'user_id' => $userId,
                'permissions' => $permissions,
                'guard' => $guard
            ]);
            return false;
        }

        foreach ($permissions as $permission) {
            if ($user->hasPermissionTo($permission, $guard)) {
                Log::debug('UserPermissionService: User has required permission', [
                    'user_id' => $userId,
                    'found_permission' => $permission,
                    'guard' => $guard
                ]);
                return true;
            }
        }

        Log::debug('UserPermissionService: User has none of the required permissions', [
            'user_id' => $userId,
            'permissions' => $permissions,
            'guard' => $guard
        ]);

        return false;
    }

    /**
     * Check if user exists.
     */
    public function userExists(int $userId): bool
    {
        $exists = User::where('id', $userId)->exists();
        
        Log::debug('UserPermissionService: User existence check', [
            'user_id' => $userId,
            'exists' => $exists
        ]);

        return $exists;
    }

    /**
     * Clear permission cache for specific user.
     */
    public function clearUserPermissionCache(int $userId): void
    {
        $patterns = [
            self::CACHE_PREFIX . "{$userId}_web",
            self::CACHE_PREFIX . "{$userId}_backpack",
        ];

        foreach ($patterns as $key) {
            Cache::forget($key);
        }

        Log::info('UserPermissionService: Cleared permission cache for user', [
            'user_id' => $userId
        ]);
    }

    /**
     * Clear all permission cache.
     */
    public function clearAllPermissionCache(): void
    {
        Cache::flush();
        Log::info('UserPermissionService: Cleared all permission cache');
    }
}
