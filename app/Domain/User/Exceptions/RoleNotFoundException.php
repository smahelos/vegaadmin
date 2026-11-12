<?php

declare(strict_types=1);

namespace App\Domain\User\Exceptions;

use DomainException;

final class RoleNotFoundException extends DomainException
{
    public function __construct(string $role)
    {
        parent::__construct('Role ' . $role . ' not found');
    }
}
