<?php

namespace App\Domain\Shared\Cache\Contracts;

interface CacheServiceInterface
{
    /**
     * Store and retrieve value from cache. If $data is a callable it will be executed
     * only when the key is missing and its return value will be cached.
     *
     * Typical usage:
     * $value = $cacheService->remember('dashboard.stats', fn() => $expensiveCall(), 600, ['dashboard']);
     *
     * @param string $key Unique cache key.
     * @param mixed|callable $data Value to store OR callable that returns the value to cache.
     * @param int $ttl Time To Live in seconds.
     * @param array<int, string> $tags Optional cache tags used for grouped invalidation.
     * @return mixed Returns cached value (existing or freshly computed/stored).
     */
    public function remember(string $key, mixed $data, int $ttl = 3600, array $tags = []): mixed;

    /**
     * Get value from cache by key.
     *
     * @param string $key Cache key.
     * @return mixed Cached value or null when not found (depending on underlying store behaviour).
     */
    public function get(string $key): mixed;

    /**
     * Put value into cache overriding any existing value.
     *
     * @param string $key Cache key.
     * @param mixed $data Value to store.
     * @param int $ttl Time To Live in seconds.
     * @param array<int, string> $tags Optional cache tags.
     * @return bool True on success, false otherwise.
     */
    public function put(string $key, mixed $data, int $ttl = 3600, array $tags = []): bool;

    /**
     * Remove value from cache.
     *
     * @param string $key Cache key to remove.
     * @return bool True if the key was removed (or did not exist depending on store), false on failure.
     */
    public function forget(string $key): bool;

    /**
     * Invalidate all cache entries that share the provided tags.
     *
     * @param array<int, string> $tags Tags to flush.
     * @return bool True after tags were flushed.
     */
    public function invalidateTags(array $tags): bool;

    /**
     * Build a standardized user-related key (namespaced by user id).
     *
     * @param int $userId ID of the user.
     * @param string $suffix Suffix describing cached content.
     * @return string Generated cache key (e.g. user_15_profile_stats).
     */
    public function userKey(int $userId, string $suffix): string;

    /**
     * Build a standardized global (system-wide) key.
     *
     * @param string $suffix Suffix describing global content.
     * @return string Generated cache key (e.g. global_currencies_list).
     */
    public function globalKey(string $suffix): string;

    /**
     * Increment a numeric cache value by the specified amount.
     *
     * @param string $key Cache key.
     * @param int $amount Amount to increment by (default 1).
     * @return bool True on success, false otherwise.
     */
    public function increment(string $key, int $amount): bool;
}
