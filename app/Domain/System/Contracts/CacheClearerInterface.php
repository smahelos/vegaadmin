<?php

declare(strict_types=1);

namespace App\Domain\System\Contracts;

use App\Domain\System\Enums\CacheKind;
use App\Domain\System\Exceptions\CacheClearFailedException;

interface CacheClearerInterface
{
    /**
     * @throws CacheClearFailedException
     */
    public function clear(CacheKind $cache): void;
}
