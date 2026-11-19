<?php

declare(strict_types=1);

namespace App\Domain\User\Exceptions;

use DomainException;

/**
 * The new password is on the list of passwords too common to accept; the form shows it as an error of the
 * password field.
 */
final class CommonPasswordException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Password is too common');
    }
}
