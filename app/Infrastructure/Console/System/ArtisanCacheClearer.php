<?php

declare(strict_types=1);

namespace App\Infrastructure\Console\System;

use App\Domain\System\Contracts\CacheClearerInterface;
use App\Domain\System\Enums\CacheKind;
use App\Domain\System\Exceptions\CacheClearFailedException;
use Illuminate\Support\Facades\Artisan;
use Throwable;

final class ArtisanCacheClearer implements CacheClearerInterface
{
    public function clear(CacheKind $cache): void
    {
        $command = match ($cache) {
            CacheKind::APPLICATION => 'cache:clear',
            CacheKind::CONFIG => 'config:clear',
            CacheKind::VIEWS => 'view:clear',
            CacheKind::ROUTES => 'route:clear',
        };

        // Any failure of the framework command (an unreachable cache store) becomes the port's exception
        try {
            $exitCode = Artisan::call($command);
        } catch (Throwable $e) {
            throw new CacheClearFailedException(
                "Clearing the {$cache->value} cache failed",
                0,
                $e,
            );
        }

        if ($exitCode !== 0) {
            throw new CacheClearFailedException("Clearing the {$cache->value} cache exited with {$exitCode}");
        }
    }
}
