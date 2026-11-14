<?php

declare(strict_types=1);

namespace App\Domain\Payment\Exceptions;

use DomainException;

final class ActiveSubscriptionExistsException extends DomainException
{
    public function __construct()
    {
        parent::__construct('The user already has an active subscription');
    }
}
