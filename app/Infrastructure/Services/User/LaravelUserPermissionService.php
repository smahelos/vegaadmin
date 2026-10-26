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

        return Cache::remember(
            $cacheKey,
            self::CACHE_TTL,
            function () use ($userId, $guard) {
                $user = User::find($userId);

                if (! $user) {
                    Log::debug('UserPermissionService: User not found', [
                        'user_id' => $userId,
                        'guard' => $guard,
                    ]);

                    return [];
                }

                $permissions = $user->getAllPermissions();
                $permissionNames = $permissions->pluck('name')->unique()->values()->toArray();

                Log::debug('UserPermissionService: Retrieved user permissions', [
                    'user_id' => $userId,
                    'guard' => $guard,
                    'permissions_count' => count($permissionNames),
                ]);

                return $permissionNames;
            },
        );
    }

    /**
     * Check if user exists.
     */
    public function userExists(int $userId): bool
    {
        $exists = User::where('id', $userId)->exists();

        Log::debug('UserPermissionService: User existence check', [
            'user_id' => $userId,
            'exists' => $exists,
        ]);

        return $exists;
    }
}
