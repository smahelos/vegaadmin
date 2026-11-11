<?php

declare(strict_types=1);

namespace App\Domain\User\Exceptions;

use App\Domain\User\Enums\Permission;
use DomainException;

final class PermissionDeniedException extends DomainException
{
    public function __construct(Permission $permission)
    {
        parent::__construct('Permission denied: ' . $permission->value);
    }
}
