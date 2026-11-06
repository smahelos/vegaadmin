<?php

namespace App\Infrastructure\Shared\Cache;

use App\Domain\Shared\Cache\Contracts\CacheServiceInterface;
use Illuminate\Support\Facades\Cache;

/**
 * Laravel-based implementation of CacheServiceInterface.
 * Lives in Infrastructure to keep Domain free from framework dependencies.
 */
class CacheService implements CacheServiceInterface
{
    private const DEFAULT_TTL = 3600; // 1 hour

    public function remember(
        string $key,
        mixed $data,
        int $ttl = self::DEFAULT_TTL,
        array $tags = [],
    ): mixed {
        if (is_callable($data)) {
            if ($tags) {
                $cache = Cache::tags($tags);

                return $cache->remember(
                    $key,
                    $ttl,
                    $data,
                );
            }

            return Cache::remember(
                $key,
                $ttl,
                $data,
            );
        }

        if ($tags) {
            $cache = Cache::tags($tags);
            $cache->put(
                $key,
                $data,
                $ttl,
            );
        } else {
            Cache::put(
                $key,
                $data,
                $ttl,
            );
        }

        return $data;
    }

    public function get(string $key): mixed
    {
        return Cache::get($key);
    }

    public function put(
        string $key,
        mixed $data,
        int $ttl = self::DEFAULT_TTL,
        array $tags = [],
    ): bool {
        if ($tags) {
            $cache = Cache::tags($tags);

            return $cache->put(
                $key,
                $data,
                $ttl,
            );
        }

        return Cache::put(
            $key,
            $data,
            $ttl,
        );
    }

    public function forget(string $key): bool
    {
        return Cache::forget($key);
    }

    public function invalidateTags(array $tags): bool
    {
        if (!empty($tags)) {
            $cache = Cache::tags($tags);
            $cache->flush();
        }

        return true;
    }

    public function globalKey(string $suffix): string
    {
        return "global_{$suffix}";
    }
}
