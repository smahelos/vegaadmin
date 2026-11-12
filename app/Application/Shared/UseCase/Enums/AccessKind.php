<?php

declare(strict_types=1);

namespace App\Application\Shared\UseCase\Enums;

enum AccessKind: string
{
    case PERMISSION = 'permission';
    case ALL_OWNERS = 'all_owners';
    case PUBLIC = 'public';
    case SYSTEM = 'system';
}
