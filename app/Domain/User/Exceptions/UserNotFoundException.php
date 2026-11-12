<?php

declare(strict_types=1);

namespace App\Domain\User\Exceptions;

use DomainException;

final class UserNotFoundException extends DomainException
{
    public function __construct(string $user)
    {
        parent::__construct('User ' . $user . ' not found');
    }
}
