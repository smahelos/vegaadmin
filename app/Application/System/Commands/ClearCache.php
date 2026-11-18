<?php

declare(strict_types=1);

namespace App\Application\System\Commands;

use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\CallsOutsideDatabaseInterface;
use App\Application\Shared\UseCase\Contracts\CommandInterface;
use App\Domain\System\Enums\CacheKind;
use App\Domain\User\Enums\Permission;

/**
 * Clears caches of the application, which lie outside the database.
 *
 * @implements CommandInterface<null>
 */
final readonly class ClearCache implements CallsOutsideDatabaseInterface, CommandInterface
{
    /**
     * @param  list<CacheKind>  $caches
     */
    public function __construct(
        public array $caches,
    ) {}

    public static function access(): Access
    {
        return Access::permission(Permission::SYSTEM_CONFIGURE);
    }
}
