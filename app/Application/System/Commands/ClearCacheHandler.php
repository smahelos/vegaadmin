<?php

declare(strict_types=1);

namespace App\Application\System\Commands;

use App\Domain\System\Contracts\CacheClearerInterface;

final class ClearCacheHandler
{
    public function __construct(
        private readonly CacheClearerInterface $cacheClearer,
    ) {}

    public function handle(ClearCache $command): void
    {
        foreach ($command->caches as $cache) {
            $this->cacheClearer->clear($cache);
        }
    }
}
