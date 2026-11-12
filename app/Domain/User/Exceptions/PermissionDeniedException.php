<?php

declare(strict_types=1);

namespace App\Domain\User\Exceptions;

use DomainException;

final class PermissionDeniedException extends DomainException
{
    public function __construct(string $useCase)
    {
        parent::__construct('Permission denied: ' . $useCase);
    }
}
