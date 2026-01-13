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

    public function remember(string $key, mixed $data, int $ttl = self::DEFAULT_TTL, array $tags = []): mixed
    {
        if (is_callable($data)) {
            return $tags
                ? Cache::tags($tags)->remember($key, $ttl, $data)
                : Cache::remember($key, $ttl, $data);
        }

        if ($tags) {
            Cache::tags($tags)->put($key, $data, $ttl);
        } else {
            Cache::put($key, $data, $ttl);
        }

        return $data;
    }

    public function get(string $key): mixed
    {
        return Cache::get($key);
    }

    public function put(string $key, mixed $data, int $ttl = self::DEFAULT_TTL, array $tags = []): bool
    {
        return $tags
            ? Cache::tags($tags)->put($key, $data, $ttl)
            : Cache::put($key, $data, $ttl);
    }

    public function forget(string $key): bool
    {
        return Cache::forget($key);
    }

    public function invalidateTags(array $tags): bool
    {
        if (!empty($tags)) {
            Cache::tags($tags)->flush();
        }
        return true;
    }

    public function userKey(int $userId, string $suffix): string
    {
        return "user_{$userId}_{$suffix}";
    }

    public function globalKey(string $suffix): string
    {
        return "global_{$suffix}";
    }

    public function increment(string $key, int $amount = 1): bool
    {
        $currentValue = Cache::get($key, 0);
        if (!is_numeric($currentValue)) {
            $currentValue = 0;
        }
        $newValue = $currentValue + $amount;
        return Cache::put($key, $newValue, self::DEFAULT_TTL);
    }
}
