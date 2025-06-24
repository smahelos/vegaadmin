<?php

namespace App\Contracts;

interface CacheServiceInterface
{
    /**
     * Cache data with tags for easy invalidation
     *
     * @param string $key
     * @param mixed $data
     * @param int $ttl Time to live in seconds
     * @param array $tags
     * @return mixed
     */
    public function remember(string $key, mixed $data, int $ttl = 3600, array $tags = []): mixed;

    /**
     * Get cached data
     *
     * @param string $key
     * @return mixed
     */
    public function get(string $key): mixed;

    /**
     * Store data in cache
     *
     * @param string $key
     * @param mixed $data
     * @param int $ttl
     * @param array $tags
     * @return bool
     */
    public function put(string $key, mixed $data, int $ttl = 3600, array $tags = []): bool;

    /**
     * Remove specific cache key
     *
     * @param string $key
     * @return bool
     */
    public function forget(string $key): bool;

    /**
     * Invalidate cache by tags
     *
     * @param array $tags
     * @return bool
     */
    public function invalidateTags(array $tags): bool;

    /**
     * Generate cache key for user-specific data
     *
     * @param int $userId
     * @param string $suffix
     * @return string
     */
    public function userKey(int $userId, string $suffix): string;

    /**
     * Generate cache key for global data
     *
     * @param string $suffix
     * @return string
     */
    public function globalKey(string $suffix): string;
}
