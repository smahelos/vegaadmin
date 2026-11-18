<?php

declare(strict_types=1);

namespace App\Domain\System\Enums;

/**
 * A cache of the application that an admin can clear.
 */
enum CacheKind: string
{
    case APPLICATION = 'application';
    case CONFIG = 'config';
    case VIEWS = 'views';
    case ROUTES = 'routes';
}
