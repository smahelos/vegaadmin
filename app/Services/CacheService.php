<?php

namespace App\Services;

use App\Contracts\CacheServiceInterface;
use Illuminate\Support\Facades\Cache;

class CacheService implements CacheServiceInterface
{
    /**
     * Default cache TTL in seconds (1 hour)
     */
    private const DEFAULT_TTL = 3600;

    /**
     * Cache data with tags for easy invalidation
     *
     * @param string $key
     * @param mixed $data
     * @param int $ttl Time to live in seconds
     * @param array $tags
     * @return mixed
     */
    public function remember(string $key, mixed $data, int $ttl = self::DEFAULT_TTL, array $tags = []): mixed
    {
        if (is_callable($data)) {
            return Cache::tags($tags)->remember($key, $ttl, $data);
        }

        Cache::tags($tags)->put($key, $data, $ttl);
        return $data;
    }

    /**
     * Get cached data
     *
     * @param string $key
     * @return mixed
     */
    public function get(string $key): mixed
    {
        return Cache::get($key);
    }

    /**
     * Store data in cache
     *
     * @param string $key
     * @param mixed $data
     * @param int $ttl
     * @param array $tags
     * @return bool
     */
    public function put(string $key, mixed $data, int $ttl = self::DEFAULT_TTL, array $tags = []): bool
    {
        return Cache::tags($tags)->put($key, $data, $ttl);
    }

    /**
     * Remove specific cache key
     *
     * @param string $key
     * @return bool
     */
    public function forget(string $key): bool
    {
        return Cache::forget($key);
    }

    /**
     * Invalidate cache by tags
     *
     * @param array $tags
     * @return bool
     */
    public function invalidateTags(array $tags): bool
    {
        Cache::tags($tags)->flush();
        return true;
    }

    /**
     * Generate cache key for user-specific data
     *
     * @param int $userId
     * @param string $suffix
     * @return string
     */
    public function userKey(int $userId, string $suffix): string
    {
        return "user_{$userId}_{$suffix}";
    }

    /**
     * Generate cache key for global data
     *
     * @param string $suffix
     * @return string
     */
    public function globalKey(string $suffix): string
    {
        return "global_{$suffix}";
    }
}
